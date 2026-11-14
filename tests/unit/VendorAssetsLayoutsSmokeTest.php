<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Smoke tests for locally bundled vendor assets and the public/admin layouts.
 *
 * Validates: Requirements 10.1, 10.2, 10.4, 10.5
 *
 * @internal
 */
final class VendorAssetsLayoutsSmokeTest extends CIUnitTestCase
{
    private const VENDOR_FILES = [
        'bootstrap/css/bootstrap.min.css',
        'bootstrap/js/bootstrap.bundle.min.js',
        'bootstrap-icons/font/bootstrap-icons.min.css',
        'bootstrap-icons/font/fonts/bootstrap-icons.woff2',
        'bootstrap-icons/font/fonts/bootstrap-icons.woff',
        'chartjs/chart.umd.min.js',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
    }

    private function publicHtml(): string
    {
        return view('layouts/public', [
            'lang'    => 'en',
            'contact' => ['whatsapp' => null],
        ], ['saveData' => false]);
    }

    private function adminHtml(): string
    {
        session()->set('admin_logged_in', true);

        try {
            return view('admin/layout', ['title' => 'Test'], ['saveData' => false]);
        } finally {
            session()->remove('admin_logged_in');
        }
    }

    /** @return array<string, array{0: string}> */
    public static function vendorFileProvider(): array
    {
        $out = [];

        foreach (self::VENDOR_FILES as $file) {
            $out[$file] = [$file];
        }

        return $out;
    }

    /** @dataProvider vendorFileProvider */
    public function testVendorFileExistsAndIsNotEmpty(string $file): void
    {
        $path = FCPATH . 'assets/vendor/' . $file;

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    /** @return array<string, array{0: string}> */
    public static function layoutProvider(): array
    {
        return ['public' => ['publicHtml'], 'admin' => ['adminHtml']];
    }

    /** @dataProvider layoutProvider */
    public function testLayoutUsesOnlyLocalVendorAssets(string $method): void
    {
        // esc(..., 'attr') entity-encodes slashes, so decode before matching.
        $html = html_entity_decode($this->{$method}(), ENT_QUOTES | ENT_HTML5);
        // Drop HTML comments (e.g. CI debug-view markers) so results don't depend on CI_DEBUG.
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        $this->assertStringNotContainsString('https://cdn.', $html);
        $this->assertStringNotContainsString('jsdelivr', $html);
        $this->assertStringNotContainsString('fonts.googleapis', $html);
        $this->assertStringContainsString('/assets/vendor/bootstrap/css/bootstrap.min.css', $html);
        $this->assertStringContainsString('/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css', $html);
        $this->assertStringContainsString('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js', $html);
    }

    /** @dataProvider layoutProvider */
    public function testLayoutIncludesViewportMeta(string $method): void
    {
        $this->assertStringContainsString(
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            $this->{$method}()
        );
    }

    public function testPublicLayoutLoadsTokensBeforeLandingCss(): void
    {
        $html = html_entity_decode($this->publicHtml(), ENT_QUOTES | ENT_HTML5);

        $tokens  = strpos($html, '/assets/css/tokens.css');
        $landing = strpos($html, '/assets/css/landing.css');

        $this->assertNotFalse($tokens);
        $this->assertNotFalse($landing);
        $this->assertLessThan($landing, $tokens);
    }

    public function testPublicLayoutHasNavbarToggler(): void
    {
        $html = $this->publicHtml();

        $this->assertStringContainsString('navbar-toggler', $html);
        $this->assertStringContainsString('navbar-expand-lg', $html);
    }

    public function testAdminLayoutLoadsTokensBeforeAdminCssWithoutExternalFonts(): void
    {
        $html = html_entity_decode($this->adminHtml(), ENT_QUOTES | ENT_HTML5);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        $tokens = strpos($html, '/assets/css/tokens.css');
        $admin  = strpos($html, '/assets/css/admin.css');

        $this->assertNotFalse($tokens);
        $this->assertNotFalse($admin);
        $this->assertLessThan($admin, $tokens);
        $this->assertStringNotContainsString('fonts.googleapis', $html);
        $this->assertStringNotContainsString('fonts.gstatic', $html);
    }

    public function testAdminLayoutHasOffcanvasSidebar(): void
    {
        $html = $this->adminHtml();

        $this->assertStringContainsString('offcanvas', $html);
        $this->assertStringContainsString('offcanvas-lg', $html);
    }
}
