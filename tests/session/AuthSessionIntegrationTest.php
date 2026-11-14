<?php

use App\Controllers\Admin\AuthController;
use App\Controllers\ApiAuthController;
use App\Filters\AdminAuthFilter;
use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Session\Handlers\ArrayHandler;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockSession;
use Config\Session as SessionConfig;

/**
 * Auth/session integration tests (no database, array session, stubbed API proxy).
 *
 * @internal
 */
final class AuthSessionIntegrationTest extends CIUnitTestCase
{
    /** @var MockSession&object{destroyed: int} */
    private $adminSession;

    protected function setUp(): void
    {
        parent::setUp();
        Services::reset(true);

        $config        = new SessionConfig();
        $this->adminSession = new class (new ArrayHandler($config, '127.0.0.1'), $config) extends MockSession {
            public int $destroyed = 0;

            public function destroy()
            {
                $this->destroyed++;
                $_SESSION = [];
            }
        };
        $this->adminSession->setLogger(service('logger'));
        $this->adminSession->start();
        Services::injectMock('session', $this->adminSession);
    }

    protected function tearDown(): void
    {
        Services::reset(true);
        parent::tearDown();
    }

    private function runFilter(bool $ajax = false)
    {
        $request = service('request');
        if ($ajax) {
            $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        }

        return (new AdminAuthFilter())->before($request);
    }

    private function controllerWith(array $apiResult, array $post): AuthController
    {
        $_POST = $post;
        $request = service('request');
        $request->setGlobal('post', $post);

        $stub = new class ($apiResult) extends ApiAuthController {
            public function __construct(private array $result)
            {
            }

            public function apiLogin(?array $credentials = null): array
            {
                return $this->result;
            }
        };

        $controller = new AuthController();
        $controller->initController($request, service('response'), service('logger'));

        $prop = new ReflectionProperty(AuthController::class, 'apiAuth');
        $prop->setAccessible(true);
        $prop->setValue($controller, $stub);

        return $controller;
    }

    private function assertRedirectsTo(string $path, $response): void
    {
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(site_url($path), $response->getHeaderLine('Location'));
    }

    /** Req 3.1 */
    public function testUnauthenticatedIsRedirectedToSignin(): void
    {
        $response = $this->runFilter();

        $this->assertRedirectsTo('signin', $response);
        $this->assertSame('', (string) $response->getBody(), 'No protected content may be rendered');
    }

    /** Req 3.1 */
    public function testUnauthenticatedAjaxGets401Json(): void
    {
        $response = $this->runFilter(true);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(401, json_decode($response->getBody(), true)['status']);
    }

    /** Req 3.1 */
    public function testAuthenticatedPassesAndSlidesWindow(): void
    {
        $this->adminSession->set([
            AdminAuthFilter::SESSION_FLAG  => true,
            AdminAuthFilter::LAST_ACTIVITY => time() - 100,
        ]);

        $this->assertNull($this->runFilter());
        $this->assertGreaterThanOrEqual(time() - 2, $this->adminSession->get(AdminAuthFilter::LAST_ACTIVITY));
    }

    /** Req 3.6 */
    public function testIdleThirtyMinutesDeauthenticatesAndDestroysSession(): void
    {
        $this->adminSession->set([
            AdminAuthFilter::SESSION_FLAG  => true,
            AdminAuthFilter::LAST_ACTIVITY => time() - AdminAuthFilter::IDLE_TIMEOUT,
        ]);

        $response = $this->runFilter();

        $this->assertRedirectsTo('signin', $response);
        $this->assertNull($this->adminSession->get(AdminAuthFilter::SESSION_FLAG));
        $this->assertNull($this->adminSession->get(AdminAuthFilter::LAST_ACTIVITY));
        $this->assertSame(1, $this->adminSession->destroyed);
    }

    /** Req 3.6 */
    public function testJustUnderIdleLimitStaysAuthenticated(): void
    {
        $this->adminSession->set([
            AdminAuthFilter::SESSION_FLAG  => true,
            AdminAuthFilter::LAST_ACTIVITY => time() - (AdminAuthFilter::IDLE_TIMEOUT - 30),
        ]);

        $this->assertNull($this->runFilter());
    }

    /** Req 3.2 */
    public function testSessionExpirationConfigIs1800(): void
    {
        $this->assertSame(1800, (new SessionConfig())->expiration);
        $this->assertSame(1800, AdminAuthFilter::IDLE_TIMEOUT);
    }

    /** Req 3.2 */
    public function testValidLoginSetsSessionAndRedirectsToDashboard(): void
    {
        $controller = $this->controllerWith(
            ['status' => 200, 'message' => '', 'data' => ['username' => 'admin'], 'total' => 0],
            ['username' => 'admin', 'password' => 'secret']
        );

        $response = $controller->submit();

        $this->assertRedirectsTo('dashboard', $response);
        $this->assertTrue($this->adminSession->get(AdminAuthFilter::SESSION_FLAG));
        $this->assertGreaterThan(0, (int) $this->adminSession->get(AdminAuthFilter::LAST_ACTIVITY));
        $this->assertSame('admin', $this->adminSession->get('admin_username'));
        $this->assertNull($this->runFilter(), 'Filter must now let the admin through');
    }

    /** Req 3.3 */
    public function testInvalidLoginShowsGenericErrorAndStaysUnauthenticated(): void
    {
        $controller = $this->controllerWith(
            ['status' => 401, 'message' => 'username not found', 'data' => [], 'total' => 0],
            ['username' => 'admin', 'password' => 'wrong']
        );

        $response = $controller->submit();

        $this->assertRedirectsTo('signin', $response);
        $this->assertNotTrue($this->adminSession->get(AdminAuthFilter::SESSION_FLAG));

        $flash = $this->adminSession->getFlashdata('error') ?? $this->adminSession->get('_ci_old_input') ?? null;
        $this->assertSame(AuthController::GENERIC_ERROR, $flash ?? $this->flashError($response));
        $this->assertRedirectsTo('signin', $this->runFilter());
    }

    /** Req 3.4 */
    public function testSignoutDestroysSessionAndRedirects(): void
    {
        $this->adminSession->set([
            AdminAuthFilter::SESSION_FLAG  => true,
            AdminAuthFilter::LAST_ACTIVITY => time(),
        ]);

        $response = (new AuthController())->signout();

        $this->assertRedirectsTo('signin', $response);
        $this->assertNull($this->adminSession->get(AdminAuthFilter::SESSION_FLAG));
        $this->assertSame(1, $this->adminSession->destroyed);
        $this->assertRedirectsTo('signin', $this->runFilter());
    }

    private function flashError($response): ?string
    {
        // RedirectResponse::with() stores flash via the session service.
        return $this->adminSession->getFlashdata('error');
    }
}
