<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Example tests for the redesigned admin views.
 *
 * Validates: Requirements 10.16, 10.19, 11.16, 11.17
 *
 * @internal
 */
final class AdminViewsTest extends CIUnitTestCase
{
    private const LIST_PAGES = ['news', 'fleet', 'faq', 'gallery'];

    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
    }

    private function render(string $view, array $data = []): string
    {
        session()->set('admin_logged_in', true);

        try {
            return view($view, $data + ['title' => 'Test'], ['saveData' => false]);
        } finally {
            session()->remove('admin_logged_in');
        }
    }

    public function testAdminLayoutIsNoindex(): void
    {
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow">',
            $this->render('admin/layout')
        );
    }

    public function testSigninIsNoindex(): void
    {
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow">',
            $this->render('admin/signin')
        );
    }

    public function testSigninInputsAreLabelled(): void
    {
        $html = $this->render('admin/signin');

        foreach (['username', 'password'] as $id) {
            $this->assertMatchesRegularExpression('/<label[^>]+for="' . $id . '"/', $html);
            $this->assertMatchesRegularExpression('/<input[^>]+id="' . $id . '"[^>]+name="' . $id . '"/', $html);
        }
    }

    public function testActiveNavItemHasAriaCurrentPage(): void
    {
        $request = service('request');
        $original = $request->getUri()->getPath();
        $request->getUri()->setPath('/admin/news/create');

        try {
            $html = $this->render('admin/layout');
        } finally {
            $request->getUri()->setPath($original);
        }


        $this->assertStringContainsString('offcanvas-lg', $html);
        $this->assertStringContainsString('id="adminSidebar"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertSame(
            substr_count($html, 'nav-link active'),
            substr_count($html, 'aria-current="page"')
        );
    }

    public function testProfileCardShowsInitials(): void
    {
        session()->set('admin_username', 'John Doe');

        try {
            $html = $this->render('admin/layout');
        } finally {
            session()->remove('admin_username');
        }

        $this->assertStringContainsString('class="profile-card', $html);
        $this->assertMatchesRegularExpression('/class="avatar"[^>]*>JD</', $html);
    }

    public function testSigninHasNoSidebar(): void
    {
        session()->remove('admin_logged_in');
        $html = view('admin/signin', ['title' => 'Sign in'], ['saveData' => false]);

        $this->assertStringNotContainsString('id="adminSidebar"', $html);
        $this->assertStringNotContainsString('admin-sidebar', $html);
        $this->assertStringContainsString('auth-card', $html);
    }

    /** @return array<string, array{0: string}> */
    public static function listPageProvider(): array
    {
        $out = [];

        foreach (self::LIST_PAGES as $page) {
            $out[$page] = [$page];
        }

        return $out;
    }

    /** @dataProvider listPageProvider */
    public function testListPagesUseTableResponsive(string $page): void
    {
        $html = $this->render('admin/' . $page . '/index');

        $this->assertStringContainsString('table-responsive', $html);
    }

    public function testSiteConfigHasSeoTabAndFields(): void
    {
        $html = $this->render('admin/site-config/index', ['config' => []]);

        $this->assertStringContainsString('id="config-tab-seo"', $html);
        $this->assertStringContainsString('id="config-pane-seo"', $html);

        $fields = ['og_image'];

        foreach (['site_name', 'meta_description', 'meta_keywords'] as $base) {
            foreach (['en', 'id', 'ms'] as $lang) {
                $fields[] = $base . '_' . $lang;
            }
        }

        foreach ($fields as $name) {
            $this->assertStringContainsString('name="' . $name . '"', $html, "Missing SEO field {$name}");
        }
    }
}
