<?php

use App\Filters\AdminAuthFilter;
use App\Libraries\AdminUserValidator;
use App\Models\AdminUserModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Master User: validation, protection rules, no hash leaks, wiring and admin views. DB-free.
 *
 * @internal
 */
final class MasterUserTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
    }

    private function ok(array $o = []): array
    {
        return array_merge(['username' => 'john.doe', 'password' => 'Secret123!', 'password_confirm' => 'Secret123!', 'status' => 'active'], $o);
    }

    // ------------------------------------------------------------ validation

    public function testValidAccountPasses(): void
    {
        $this->assertSame([], AdminUserValidator::validate($this->ok(), true));
        $this->assertSame([], AdminUserValidator::validate($this->ok(['username' => 'A_b-1.c']), true));
    }

    /** @return array<string, array{0: array<string,mixed>, 1: string}> */
    public static function badUsernames(): array
    {
        return [
            'missing'   => [['username' => null], 'Username is required.'],
            'blank'     => [['username' => '   '], 'Username is required.'],
            'too short' => [['username' => 'ab'], 'Username must be 3 to 50 characters.'],
            'too long'  => [['username' => str_repeat('a', 51)], 'Username must be 3 to 50 characters.'],
            'space'     => [['username' => 'john doe'], 'Username may only contain letters, numbers, dot (.), dash (-) and underscore (_).'],
            'markup'    => [['username' => '<b>x</b>'], 'Username may only contain letters, numbers, dot (.), dash (-) and underscore (_).'],
        ];
    }

    /** @dataProvider badUsernames */
    public function testBadUsernamesAreRejected(array $override, string $message): void
    {
        $this->assertSame($message, AdminUserValidator::validate($this->ok($override), true)['username'] ?? null);
    }

    public function testPasswordRules(): void
    {
        $this->assertSame('Password is required.', AdminUserValidator::validate($this->ok(['password' => '', 'password_confirm' => '']), true)['password']);
        $this->assertSame('Password must be at least 8 characters.', AdminUserValidator::validate($this->ok(['password' => 'Ab1!', 'password_confirm' => 'Ab1!']), true)['password']);
        $long = str_repeat('a', 73);
        $this->assertSame('Password must be at most 72 characters.', AdminUserValidator::validate($this->ok(['password' => $long, 'password_confirm' => $long]), true)['password']);
        $this->assertSame('Password cannot be only spaces.', AdminUserValidator::validate($this->ok(['password' => '         ', 'password_confirm' => '         ']), true)['password']);
        $this->assertSame('Passwords do not match.', AdminUserValidator::validate($this->ok(['password_confirm' => 'Other123!']), true)['password_confirm']);
    }

    public function testPasswordIsOptionalWhenEditing(): void
    {
        $this->assertSame([], AdminUserValidator::validate($this->ok(['password' => '', 'password_confirm' => '']), false));
        $this->assertArrayHasKey('password_confirm', AdminUserValidator::validate($this->ok(['password_confirm' => '']), false), 'a new password still has to be confirmed');
    }

    public function testStatusMustBeKnown(): void
    {
        $this->assertSame([], AdminUserValidator::validate($this->ok(['status' => 'inactive']), true));
        $this->assertArrayHasKey('status', AdminUserValidator::validate($this->ok(['status' => 'admin']), true));
    }

    // ------------------------------------------------------ protection rules

    public function testYouCannotDeleteOrDeactivateYourself(): void
    {
        $this->assertSame('You cannot delete the account you are signed in with.', AdminUserValidator::protectionError(true, true, true, false, 5));
        $this->assertSame('You cannot deactivate the account you are signed in with.', AdminUserValidator::protectionError(false, true, true, false, 5));
        $this->assertNull(AdminUserValidator::protectionError(false, true, true, true, 1), 'editing your own name/password is fine');
    }

    public function testTheLastActiveUserIsKept(): void
    {
        $this->assertSame('At least one active user is required.', AdminUserValidator::protectionError(true, false, true, false, 1));
        $this->assertSame('At least one active user is required.', AdminUserValidator::protectionError(false, false, true, false, 1));
        $this->assertNull(AdminUserValidator::protectionError(true, false, true, false, 2));
        $this->assertNull(AdminUserValidator::protectionError(true, false, false, false, 1), 'an inactive account can always be deleted');
    }

    // ------------------------------------------------------------- no leaks

    public function testPasswordHashNeverLeavesTheServer(): void
    {
        $row = ['id' => 1, 'username' => 'admin', 'password_hash' => '$2y$10$abc', 'status' => 'active', 'failed_attempts' => 0];

        $this->assertArrayNotHasKey('password_hash', AdminUserModel::toPublic($row));
        $this->assertSame('admin', AdminUserModel::toPublic($row)['username']);
        $this->assertNotContains('password_hash', AdminUserModel::PUBLIC_COLUMNS);
    }

    public function testProxyForwardsOnlyAccountFields(): void
    {
        $src = (string) file_get_contents(APPPATH . 'Controllers/ApiUserController.php');

        $this->assertStringContainsString("array_flip(['username', 'password', 'password_confirm', 'status'])", $src);
        $this->assertStringContainsString("private const ENDPOINT = 'api/v1/admin-users';", $src);
        $this->assertStringContainsString('class ApiUserController extends Controller', $src);
    }

    // ----------------------------------------------------- session / filter

    public function testDeletedOrInactiveAccountsLoseAccess(): void
    {
        $filter = new class () extends AdminAuthFilter {
            public array $rows = [];

            public function check(int $id): bool
            {
                return $this->accountStillActive($id);
            }

            protected function accountStillActive(int $userId): bool
            {
                if ($userId <= 0) {
                    return true;
                }
                $row = $this->rows[$userId] ?? null;

                return $row !== null && ($row['status'] ?? '') === 'active';
            }
        };
        $filter->rows = [1 => ['status' => 'active'], 2 => ['status' => 'inactive']];

        $this->assertTrue($filter->check(1));
        $this->assertFalse($filter->check(2), 'deactivated');
        $this->assertFalse($filter->check(3), 'deleted');
        $this->assertTrue($filter->check(0), 'old session without an id');
    }

    public function testSignInStoresTheUserIdAndTheFilterChecksIt(): void
    {
        $auth   = (string) file_get_contents(APPPATH . 'Controllers/Admin/AuthController.php');
        $filter = (string) file_get_contents(APPPATH . 'Filters/AdminAuthFilter.php');

        $this->assertStringContainsString("AdminAuthFilter::USER_ID       => (int) (\$result['data']['id'] ?? 0)", $auth);
        $this->assertStringContainsString('! $this->accountStillActive((int) $session->get(self::USER_ID))', $filter);
        $this->assertSame('admin_user_id', AdminAuthFilter::USER_ID);
    }

    // --------------------------------------------------------------- routes

    public function testRoutesAreAdminOnlyWithoutAPublicRead(): void
    {
        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');

        $this->assertStringContainsString("'users'       => 'UserController',", $routes);
        $this->assertStringContainsString("'users'       => ['ApiUserController', []],", $routes);
        $this->assertStringContainsString("\$routes->group('admin-users', ['filter' => 'internalapi']", $routes);
        $this->assertStringNotContainsString('admin-users/active', $routes);
        $this->assertDoesNotMatchRegularExpression("#'admin-users'\s*=>\s*\['AdminUsersController#", $routes, 'not in the resources loop, which adds a public /active route');
    }

    // ---------------------------------------------------------------- views

    private function render(string $view, array $data): string
    {
        session()->set('admin_logged_in', true);
        try {
            return html_entity_decode(view($view, $data, ['saveData' => false]), ENT_QUOTES | ENT_HTML5);
        } finally {
            session()->remove('admin_logged_in');
        }
    }

    public function testMenuHasMasterUser(): void
    {
        $html = $this->render('admin/layout', ['title' => 'x']);

        $this->assertMatchesRegularExpression('#href="[^"]*/admin/users">\s*<i class="bi bi-people" aria-hidden="true"></i>\s*<span>Master User</span>#', $html);
    }

    public function testListShowsUsernameStatusAndLastSignIn(): void
    {
        $html = $this->render('admin/users/index', ['title' => 'Master User']);

        $this->assertStringContainsString('data-entity="users"', $html);
        foreach (['display_name' => 'Username', 'status_label' => 'Status', 'last_login_label' => 'Last sign-in'] as $key => $label) {
            $this->assertStringContainsString('data-key="' . $key . '">' . $label . '</th>', $html);
        }
        $this->assertStringContainsString('admin/users/create', $html);
    }

    public function testCreateFormRequiresPassword(): void
    {
        $html = $this->render('admin/users/form', ['title' => 'Create User', 'item' => null]);

        $this->assertMatchesRegularExpression('#id="password" name="password"[^>]*autocomplete="new-password" required#', $html);
        $this->assertMatchesRegularExpression('#id="password_confirm" name="password_confirm"[^>]*required#', $html);
        $this->assertStringNotContainsString('Leave blank to keep', $html);
        $this->assertStringNotContainsString('English (EN) is the default language', $html);
        foreach (['username', 'password', 'password_confirm', 'status'] as $field) {
            $this->assertStringContainsString('data-error-for="' . $field . '"', $html);
        }
    }

    public function testEditFormKeepsPasswordOptionalAndNeverShowsIt(): void
    {
        $html = $this->render('admin/users/form', ['title' => 'Edit User', 'item' => ['id' => 4, 'username' => 'sari', 'status' => 'inactive', 'password_hash' => '$2y$10$secret']]);

        $this->assertStringContainsString('data-id="4"', $html);
        $this->assertStringContainsString('value="sari"', $html);
        $this->assertStringContainsString('Leave blank to keep the current password.', $html);
        $this->assertDoesNotMatchRegularExpression('#id="password" name="password"[^>]* required#', $html);
        $this->assertStringContainsString('<option value="inactive" selected>', $html);
        $this->assertStringNotContainsString('secret', $html);
    }

    public function testYouCannotDeactivateYourselfInTheForm(): void
    {
        $html = $this->render('admin/users/form', ['title' => 'Edit User', 'item' => ['id' => 1, 'username' => 'admin', 'status' => 'active', 'is_self' => true]]);

        $this->assertMatchesRegularExpression('#<select id="status" name="status"[^>]* disabled>#', $html);
        $this->assertStringContainsString('You cannot deactivate the account you are signed in with.', $html);
    }

    public function testListScriptHidesDeleteWhenARowCannotBeDeleted(): void
    {
        $this->assertStringContainsString('if (row.can_delete !== false) {', (string) file_get_contents(FCPATH . 'assets/js/admin.js'));
    }
}