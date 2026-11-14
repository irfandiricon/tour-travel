<?php
use App\Controllers\BaseController;
use CodeIgniter\Controller;
use PHPUnit\Framework\TestCase;

/**
 * Reflection-based architectural conformance checks (no database needed).
 *
 * Validates: Requirements 9.1, 9.2, 9.6, 9.7
 *
 * Documented exceptions (per design): PublicBaseController and the public landing
 * controllers built on it (LandingController, LanguageController) are public pages with
 * no admin menu/user context, so they extend CodeIgniter\Controller directly rather than
 * BaseController. Page controllers in the admin area (App\Controllers\Admin\*) and Home
 * extend BaseController.
 *
 * @internal
 */
final class ArchitectureConformanceTest extends TestCase
{
    private const CONTROLLER_DIR = APPPATH . 'Controllers';

    /** Page controllers outside Admin\ that must extend BaseController. */
    private const BASE_PAGE_CONTROLLERS = ['Home'];

    /** Public page controllers that legitimately extend Controller (via PublicBaseController). */
    private const PUBLIC_CONTROLLERS = ['PublicBaseController', 'LandingController', 'ListingController', 'LanguageController', 'SeoController', 'TrackController'];

    /** Members an Admin controller must not set up in its constructor (BaseController provides them). */
    private const FORBIDDEN_ADMIN_ASSIGNMENTS = ['authCheck', 'menu', 'menuData', 'service', 'user'];

    /**
     * @return list<class-string> classes in app/Controllers (optionally a sub-namespace), excluding BaseController
     */
    private function classesIn(string $subNamespace = ''): array
    {
        $dir = self::CONTROLLER_DIR . ($subNamespace === '' ? '' : DIRECTORY_SEPARATOR . $subNamespace);
        $out = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $name  = basename($file, '.php');
            $class = 'App\\Controllers\\' . ($subNamespace === '' ? '' : $subNamespace . '\\') . $name;
            if ($class === BaseController::class) {
                continue;
            }
            $out[] = $class;
        }
        sort($out);

        return $out;
    }

    /** @return list<class-string> root-level Api*Controller classes */
    private function apiControllers(): array
    {
        return array_values(array_filter(
            $this->classesIn(),
            static fn (string $c): bool => (bool) preg_match('/\\\\Api[A-Za-z]*Controller$/', $c)
        ));
    }

    /** @return list<class-string> */
    private function adminControllers(): array
    {
        return $this->classesIn('Admin');
    }

    /** Property names assigned as `$this->x = ...` in the class's own constructor. */
    private function constructorAssignments(ReflectionClass $class): array
    {
        $ctor = $class->getConstructor();
        if ($ctor === null || $ctor->getDeclaringClass()->getName() !== $class->getName()) {
            return [];
        }

        $lines  = file($ctor->getFileName());
        $source = implode('', array_slice($lines, $ctor->getStartLine() - 1, $ctor->getEndLine() - $ctor->getStartLine() + 1));

        // Token scan: ignore comments/strings, match T_VARIABLE($this) -> T_STRING = (not ==).
        $tokens = token_get_all('<?php ' . $source);
        $tokens = array_values(array_filter(
            $tokens,
            static fn ($t): bool => ! (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
        ));

        $names = [];
        $n     = count($tokens);
        for ($i = 0; $i + 3 < $n; $i++) {
            if (
                is_array($tokens[$i]) && $tokens[$i][0] === T_VARIABLE && $tokens[$i][1] === '$this'
                && is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_OBJECT_OPERATOR
                && is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_STRING
                && $tokens[$i + 3] === '='
            ) {
                $names[] = $tokens[$i + 2][1];
            }
        }

        return array_values(array_unique($names));
    }

    public function testControllersAreDiscovered(): void
    {
        $this->assertNotEmpty($this->apiControllers());
        $this->assertNotEmpty($this->adminControllers());
    }

    /** Req 9.1 */
    public function testApiControllersExtendControllerNotBaseController(): void
    {
        foreach ($this->apiControllers() as $class) {
            $ref = new ReflectionClass($class);
            $this->assertSame(Controller::class, $ref->getParentClass()->getName(), "$class must extend CodeIgniter\\Controller");
            $this->assertFalse($ref->isSubclassOf(BaseController::class), "$class must not extend BaseController");
        }
    }

    /** Req 9.2 */
    public function testAdminAndPageControllersExtendBaseController(): void
    {
        $classes = $this->adminControllers();
        foreach (self::BASE_PAGE_CONTROLLERS as $name) {
            $classes[] = 'App\\Controllers\\' . $name;
        }
        foreach ($classes as $class) {
            $this->assertTrue(
                (new ReflectionClass($class))->isSubclassOf(BaseController::class),
                "$class must extend BaseController"
            );
        }
    }

    /** Req 9.2 (documented exception): public controllers extend Controller, not BaseController. */
    public function testPublicControllersAreDocumentedExceptions(): void
    {
        foreach (self::PUBLIC_CONTROLLERS as $name) {
            $ref = new ReflectionClass('App\\Controllers\\' . $name);
            $this->assertTrue($ref->isSubclassOf(Controller::class), "$name must be a Controller");
            $this->assertFalse($ref->isSubclassOf(BaseController::class), "$name is a public page controller (design exception)");
        }

        // Every non-Api, non-Admin root controller is accounted for as either BaseController page or public.
        $known = array_merge(self::BASE_PAGE_CONTROLLERS, self::PUBLIC_CONTROLLERS);
        foreach ($this->classesIn() as $class) {
            if (in_array($class, $this->apiControllers(), true)) {
                continue;
            }
            $this->assertContains(substr($class, strlen('App\\Controllers\\')), $known, "$class is not classified");
        }
    }

    /** Req 9.6 */
    public function testConstructorAssignedPropertiesAreDeclared(): void
    {
        $classes = array_merge($this->apiControllers(), $this->adminControllers());
        $checked = 0;
        foreach ($classes as $class) {
            $ref = new ReflectionClass($class);
            foreach ($this->constructorAssignments($ref) as $prop) {
                $this->assertTrue($ref->hasProperty($prop), "$class assigns \$this->$prop in its constructor without declaring it");
                $checked++;
            }
        }
        $this->assertGreaterThan(0, $checked);
    }

    /** Req 12.15, 12.28 */
    public function testDashboardControllersFollowBasePattern(): void
    {
        $api = new ReflectionClass(\App\Controllers\ApiDashboardController::class);
        $this->assertSame(Controller::class, $api->getParentClass()->getName());

        $admin = new ReflectionClass(\App\Controllers\Admin\DashboardController::class);
        $this->assertSame(BaseController::class, $admin->getParentClass()->getName());

        foreach ([$api, $admin] as $ref) {
            foreach ($this->constructorAssignments($ref) as $prop) {
                $this->assertTrue($ref->hasProperty($prop), $ref->getName() . " must declare \$$prop");
            }
        }
    }

    /** Req 12.15, 12.28: pure libraries have no DB, service or cURL access. */
    public function testPureLibrariesHaveNoInfrastructureReferences(): void
    {
        foreach (['DeviceDetector', 'VisitorHasher', 'DashboardStats', 'SeoMetaBuilder'] as $name) {
            $file = APPPATH . 'Libraries' . DIRECTORY_SEPARATOR . $name . '.php';
            $this->assertFileExists($file);

            // Scan code tokens only so comments/strings cannot cause false positives.
            $code = '';
            foreach (token_get_all((string) file_get_contents($file)) as $t) {
                if (is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true)) {
                    continue;
                }
                $code .= is_array($t) ? $t[1] : $t;
            }
            foreach (['db_connect', 'service(', 'Services::', 'curl_'] as $needle) {
                $this->assertStringNotContainsString($needle, $code, "$name must not reference $needle");
            }
        }
    }

    /** Req 9.7 */
    public function testAdminConstructorsDoNotDuplicateBaseControllerSetup(): void
    {
        foreach ($this->adminControllers() as $class) {
            $assigned = $this->constructorAssignments(new ReflectionClass($class));
            $this->assertSame(
                [],
                array_values(array_intersect($assigned, self::FORBIDDEN_ADMIN_ASSIGNMENTS)),
                "$class constructor must not assign authCheck/menu/service/user"
            );
        }
    }
}
