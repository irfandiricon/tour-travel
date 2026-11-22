<?php

use App\Libraries\AdminSearch;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Admin data tables: rows-per-page selector, "Showing x to y of z entries" and numbered pagination,
 * shared by every admin list (one partial + admin.js).
 *
 * @internal
 */
final class AdminDataTableTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
    }

    private function render(string $view): string
    {
        \Config\Services::resetSingle('renderer');
        session()->set('admin_logged_in', true);
        try {
            return view($view, ['title' => 'List'], ['saveData' => false]);
        } finally {
            session()->remove('admin_logged_in');
        }
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function lists(): array
    {
        return [
            'news'         => ['admin/news/index', 'news'],
            'fleet'        => ['admin/fleet/index', 'fleet'],
            'faq'          => ['admin/faq/index', 'faq'],
            'gallery'      => ['admin/gallery/index', 'gallery'],
            'testimonials' => ['admin/testimonials/index', 'testimonials'],
            'users'        => ['admin/users/index', 'users'],
        ];
    }

    /** @dataProvider lists */
    public function testEveryAdminListHasDataTableControls(string $view, string $entity): void
    {
        $html = $this->render($view);

        $this->assertStringContainsString('data-entity="' . $entity . '"', $html);
        $this->assertMatchesRegularExpression('#<select class="form-select form-select-sm" data-page-size aria-label="Rows per page">#', $html);
        foreach ([10, 25, 50, 100] as $size) {
            $this->assertStringContainsString('<option value="' . $size . '"', $html);
        }
        $this->assertStringContainsString('<option value="10" selected>10</option>', $html);
        $this->assertStringContainsString('<p id="list-status" class="dt-info" aria-live="polite">', $html);
        $this->assertStringContainsString('<nav class="dt-pager" aria-label="Pagination" data-admin-pager hidden>', $html);
        $this->assertStringContainsString('<ul class="pagination pagination-sm mb-0" data-page-list></ul>', $html);
        $this->assertStringNotContainsString('data-page-prev', $html, 'old Previous/Next pager is gone');
        $this->assertMatchesRegularExpression('#<label for="list-search" class="dt-search__label">Search:</label>\s*<input type="search" id="list-search" class="form-control form-control-sm" data-list-search\s+maxlength="100"#', $html);
        $this->assertStringContainsString('aria-controls="list-table"', $html);
        $this->assertStringContainsString('<table id="list-table"', $html);
    }

    public function testScriptRendersNumberedPagesInfoAndPageSize(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/admin.js');

        $this->assertStringContainsString('var PAGE_SIZES = [10, 25, 50, 100];', $js);
        $this->assertStringContainsString('function pageNumbers(current, pages) {', $js);
        $this->assertStringContainsString("setStatus('Showing ' + from + ' to ' + (from + rows.length - 1) + ' of ' + total + ' entries' + filtered);", $js);
        $this->assertStringContainsString("'&per_page=' + perPage", $js);
        $this->assertStringContainsString("window.localStorage.setItem('admin-page-size:' + entity", $js, 'page size is remembered per menu');
        $this->assertStringContainsString("el.setAttribute('aria-current', 'page');", $js);
        $this->assertStringContainsString('if (lastCount <= 1 && current > 1) { current--; }', $js, 'deleting the last row of a page goes back one page');
    }

    public function testAllAdminListApisAcceptUpToOneHundredRows(): void
    {
        foreach (['ContentsFaqsController', 'ContentsGalleryController', 'ContentsTestimonialsController', 'AdminUsersController'] as $c) {
            $this->assertStringContainsString('min(100,', (string) file_get_contents(APPPATH . 'Controllers/ApiV1/' . $c . '.php'), $c);
        }
        $this->assertSame(100, \App\Libraries\Paginator::MAX_PER_PAGE, 'news and fleet use the Paginator');
    }

    public function testSearchTermIsTrimmedCollapsedAndCapped(): void
    {
        $this->assertSame('', AdminSearch::term(null));
        $this->assertSame('', AdminSearch::term(['x']));
        $this->assertSame('', AdminSearch::term("  \t "));
        $this->assertSame('jembatan barelang', AdminSearch::term("  jembatan \n  barelang "));
        $this->assertSame(AdminSearch::MAX_LENGTH, mb_strlen(AdminSearch::term(str_repeat('é', 150))));
    }

    /** @return array<string, array{0: string, 1: list<string>}> */
    public static function searchColumns(): array
    {
        return [
            'news'         => ['ContentsNewsController', ['title_en', 'title_id', 'title_ms', 'body_en', 'body_id', 'body_ms', 'status']],
            'fleet'        => ['ContentsFleetController', ['name_en', 'name_id', 'name_ms', 'description_en', 'description_id', 'description_ms', 'status']],
            'faq'          => ['ContentsFaqsController', ['question_en', 'question_id', 'question_ms', 'answer_en', 'answer_id', 'answer_ms', 'status']],
            'gallery'      => ['ContentsGalleryController', ['caption_en', 'caption_id', 'caption_ms', 'status']],
            'testimonials' => ['ContentsTestimonialsController', ['customer_name', 'review_en', 'review_id', 'review_ms', 'status']],
            'users'        => ['AdminUsersController', ['username', 'status']],
        ];
    }

    /** @dataProvider searchColumns */
    public function testEveryListSearchesItsTextColumnsInAllLanguages(string $controller, array $columns): void
    {
        $class = 'App\\Controllers\\ApiV1\\' . $controller;
        $this->assertSame($columns, constant($class . '::SEARCH_COLUMNS'));
        $this->assertNotContains('password_hash', $columns);
        $this->assertStringContainsString("AdminSearch::term(\$this->request->getGet('search'))", (string) file_get_contents(APPPATH . 'Controllers/ApiV1/' . $controller . '.php'));
    }

    public function testProxiesForwardTheSearchTerm(): void
    {
        foreach (['ApiNewsController', 'ApiFleetController', 'ApiFaqController', 'ApiGalleryController', 'ApiTestimonialController', 'ApiUserController'] as $proxy) {
            $this->assertStringContainsString("'search'   => is_string(\$this->request->getGet('search'))", (string) file_get_contents(APPPATH . 'Controllers/' . $proxy . '.php'), $proxy);
        }
    }

    public function testWildcardsAreEscapedSoTheyMatchLiterally(): void
    {
        $src = (string) file_get_contents(APPPATH . 'Libraries/AdminSearch.php');

        $this->assertStringContainsString('->escapeLikeString($term)', $src);
        $this->assertStringContainsString('$model->groupStart();', $src, 'OR-ed columns stay inside one group with the status filter');
        // Checked live against MySQL: searching "%" only finds the news item that contains "21.7%".
        $this->assertStringContainsString('$model->like($column, $escaped)', $src, 'the escaped term is what gets bound');
    }

    public function testScriptSearchesAfterTypingPausesAndIgnoresStaleAnswers(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/admin.js');

        $this->assertStringContainsString('searchTimer = window.setTimeout(runSearch, 350);', $js);
        $this->assertStringContainsString("(search !== '' ? '&search=' + encodeURIComponent(search) : '')", $js);
        $this->assertStringContainsString('if (mine !== requestId) { return; }', $js);
        $this->assertStringContainsString("'No matching entries found for \"' + search + '\".'", $js);
        $this->assertStringContainsString("if (e.key === 'Escape' && searchInput.value !== '')", $js);
    }

    public function testTablesScrollSidewaysOnNarrowScreens(): void
    {
        $css = (string) file_get_contents(FCPATH . 'assets/css/admin.css');

        $this->assertMatchesRegularExpression('/\.list-card\.table-responsive \{\s*overflow-x: auto; overflow-y: hidden;/', $css, 'beats .list-card { overflow: hidden }');
        $this->assertLessThan(strpos($css, 'overflow-x: auto; overflow-y: hidden;'), strpos($css, '.list-card { overflow: hidden; }'), 'the override comes after the rule it fixes');
        $this->assertStringContainsString('.list-card .admin-table { min-width: 720px; }', $css);
        $this->assertStringContainsString('.list-card .admin-table th { white-space: nowrap; }', $css);

        $html = $this->render('admin/faq/index');
        $this->assertMatchesRegularExpression('#<div class="list-card table-responsive bg-white border rounded" tabindex="0" role="region" aria-label="[^"]*scroll horizontally[^"]*">#', $html, 'keyboard users can scroll it too');
    }
}