<?php

use App\Controllers\LandingController;
use App\Controllers\ListingController;
use App\Controllers\PublicBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * News & Information: clickable cards, landing carousel and the detail page news/{id}/{slug}.
 * DB-free: the Api*Controller proxies are replaced with stubs.
 *
 * @internal
 */
final class NewsDetailTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'ui']);
        service('request')->setGlobal('get', []);
    }

    private function d(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
    }

    private function stub(array $data): object
    {
        return new class ($data) {
            public function __construct(private array $d)
            {
            }

            public function __call($n, $a)
            {
                return ['status' => 200, 'message' => '', 'data' => $this->d, 'total' => count($this->d)];
            }
        };
    }

    private function build(string $class, array $news, string $lang = 'en')
    {
        $ref = new ReflectionClass($class);
        $c   = $ref->newInstanceWithoutConstructor();
        foreach (['apiSiteConfig' => ['site_name_en' => 'Kimigo Travel'], 'apiNews' => $news, 'apiFleet' => [], 'apiGallery' => [], 'apiFaq' => []] as $prop => $data) {
            if ($ref->hasProperty($prop)) {
                $p = $ref->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($c, $this->stub($data));
            }
        }
        $base = new ReflectionClass(PublicBaseController::class);
        foreach (['lang' => $lang, 'request' => service('request')] as $prop => $value) {
            $p = $base->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($c, $value);
        }

        return $c;
    }

    /** @return list<array<string,mixed>> */
    private function rows(int $n = 5): array
    {
        $rows = [];
        for ($i = 1; $i <= $n; $i++) {
            $rows[] = [
                'id' => $i, 'status' => 'published', 'publish_date' => sprintf('2024-01-%02d 08:00:00', $i),
                'title_en' => "Barelang Trip {$i}", 'title_id' => "Wisata Barelang {$i}",
                'body_en' => "First paragraph {$i}.\n\nSecond paragraph {$i}.", 'body_id' => "Paragraf {$i}.",
                'image_path' => $i === 2 ? 'uploads/news/b.png' : null,
            ];
        }

        return $rows;
    }

    private function detail(array $rows, int $id, string $lang = 'en'): string
    {
        return $this->d($this->build(ListingController::class, $rows, $lang)->newsDetail($id));
    }

    // ----------------------------------------------------------------- cards

    public function testCardsLinkToTheDetailPageWithAnEnglishSlug(): void
    {
        foreach (['en', 'id'] as $lang) {
            $html = $this->d($this->build(ListingController::class, $this->rows(1), $lang)->news());

            $this->assertMatchesRegularExpression('#<a class="news-card__link" href="https?://[^"]*/news/1/barelang-trip-1">#', $html, $lang);
        }
    }

    public function testCardShowsAShortSingleLinePreview(): void
    {
        $rows = $this->rows(1);
        $rows[0]['body_en'] = str_repeat('Batam island road trip ', 30);
        $html = $this->d($this->build(ListingController::class, $rows)->news());

        preg_match('#<p class="news-card__text">([^<]*)</p>#', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertLessThanOrEqual(183, mb_strlen($m[1]));
        $this->assertStringEndsWith('...', $m[1]);
        $this->assertStringNotContainsString("\n", $m[1]);
    }

    // -------------------------------------------------------------- carousel

    public function testLandingShowsNewsInACarouselWithPrevAndNextButtons(): void
    {
        $html = $this->d($this->build(LandingController::class, $this->rows(12))->index());

        $this->assertStringContainsString('data-news-carousel', $html);
        $this->assertMatchesRegularExpression('#<div id="news-rail" class="news-rail" tabindex="0" role="group"#', $html);
        $this->assertMatchesRegularExpression('#data-news-prev\s+aria-controls="news-rail" aria-label="Previous"#', $html);
        $this->assertMatchesRegularExpression('#data-news-next\s+aria-controls="news-rail" aria-label="Next"#', $html);
        $this->assertSame(LandingController::SECTION_LIMITS['news'], substr_count($html, 'class="news-card"'));
        $this->assertLessThan(strpos($html, 'Barelang Trip 11'), strpos($html, 'Barelang Trip 12'), 'newest first');
    }

    public function testCarouselButtonsAreLocalized(): void
    {
        $html = $this->d($this->build(LandingController::class, $this->rows(2), 'id')->index());

        $this->assertStringContainsString('aria-label="Sebelumnya"', $html);
        $this->assertStringContainsString('aria-label="Berikutnya"', $html);
        $this->assertStringContainsString('Baca selengkapnya', $html);
    }

    public function testCarouselScriptIsShipped(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/landing.js');

        $this->assertStringContainsString('[data-news-carousel]', $js);
        $this->assertStringContainsString('scrollBy', $js);
    }

    // ----------------------------------------------------------- detail page

    public function testDetailPageShowsTheFullItem(): void
    {
        $html = $this->detail($this->rows(), 2);

        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
        $this->assertStringContainsString('<h1 id="page-title" class="page-title">Barelang Trip 2</h1>', $html);
        $this->assertStringContainsString("First paragraph 2.\n\nSecond paragraph 2.", $html, 'full body, line breaks kept');
        $this->assertStringContainsString('<time datetime="2024-01-02">02 January 2024</time>', $html);
        $this->assertMatchesRegularExpression('#data-lightbox-src="https?://[^"]*/uploads/news/b\.png"#', $html);
        $this->assertMatchesRegularExpression('#<a class="btn-more" href="[^"]*/news">.*Back to News & Information#s', $html);
    }

    public function testDetailPageBreadcrumbAndNavigation(): void
    {
        $html = $this->detail($this->rows(), 2);

        $this->assertMatchesRegularExpression('#<li class="breadcrumb-item"><a href="[^"]*/news">News & Information</a></li>#', $html);
        $this->assertMatchesRegularExpression('#<a class="nav-link active" href="https?://[^"]*/\#news">#', $html);
        $this->assertStringContainsString('?return=' . rawurlencode('news/2'), $html, 'language switch returns to this item');
    }

    public function testDetailPageListsOtherItemsButNotItself(): void
    {
        $html = $this->detail($this->rows(), 5);

        $this->assertStringContainsString('More news & information', $html);
        $this->assertSame(ListingController::RELATED_NEWS, substr_count($html, 'class="news-card"'));
        $this->assertDoesNotMatchRegularExpression('#news-card__link" href="[^"]*/news/5/#', $html);
        $this->assertMatchesRegularExpression('#news-card__link" href="[^"]*/news/4/barelang-trip-4"#', $html);
    }

    public function testDetailPageWithoutOtherItemsHasNoRelatedBlock(): void
    {
        $this->assertStringNotContainsString('news-related', $this->detail($this->rows(1), 1));
    }

    public function testDetailPageSeo(): void
    {
        $html = $this->detail($this->rows(), 2);

        $this->assertStringContainsString('<title>Barelang Trip 2 | Kimigo Travel</title>', $html);
        $this->assertMatchesRegularExpression('#<link rel="canonical" href="https?://[^"]*/news/2/barelang-trip-2">#', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:type" content="article">#', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="https?://[^"]*/uploads/news/b\.png">#', $html);
        $this->assertStringContainsString('"@type":"NewsArticle"', $html);
        $this->assertStringContainsString('"headline":"Barelang Trip 2"', $html);
        $this->assertStringContainsString('"position":3', $html);
    }

    public function testDetailPageIsLocalized(): void
    {
        $html = $this->detail($this->rows(), 3, 'id');

        $this->assertStringContainsString('<h1 id="page-title" class="page-title">Wisata Barelang 3</h1>', $html);
        $this->assertStringContainsString('Paragraf 3.', $html);
        $this->assertStringContainsString('Kembali ke Berita & Informasi', $html);
        $this->assertMatchesRegularExpression('#<link rel="canonical" href="https?://[^"]*/news/3/barelang-trip-3\?lang=id">#', $html);
    }

    /** @return array<string, array{0: list<array<string,mixed>>, 1: int}> */
    public static function hiddenItems(): array
    {
        $base = ['id' => 7, 'title_en' => 'Hidden', 'body_en' => 'x', 'publish_date' => '2024-01-01 00:00:00', 'status' => 'published'];

        return [
            'draft'   => [[array_merge($base, ['status' => 'draft'])], 7],
            'future'  => [[array_merge($base, ['publish_date' => '2999-01-01 00:00:00'])], 7],
            'unknown' => [[$base], 8],
            'zero'    => [[$base], 0],
        ];
    }

    /** @dataProvider hiddenItems */
    public function testDraftFutureAndUnknownItemsAre404(array $rows, int $id): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->build(ListingController::class, $rows)->newsDetail($id);
    }

    public function testDetailRoutesAndLanguageReturnAreWired(): void
    {
        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertStringContainsString("\$routes->get('news/(:num)', 'ListingController::newsDetail/\$1');", $routes);
        $this->assertStringContainsString("\$routes->get('news/(:num)/(:segment)', 'ListingController::newsDetail/\$1');", $routes);

        $lang = (string) file_get_contents(APPPATH . 'Controllers/LanguageController.php');
        $this->assertStringContainsString("preg_match('#^news/\\d{1,10}\$#', \$return)", $lang, 'only news/{digits}, no open redirect');
    }

    public function testDatesUseDayMonthNameYearInEachLanguage(): void
    {
        $this->assertSame('15 November 2026', ui_date('2026-11-15 00:49:00', 'en'));
        $this->assertSame('05 Agustus 2026', ui_date('2026-08-05', 'id'));
        $this->assertSame('05 Ogos 2026', ui_date('2026-08-05', 'ms'));
        $this->assertSame('01 Mei 2026', ui_date('2026-05-01', 'id'));
        $this->assertSame('', ui_date('', 'en'));
        $this->assertSame('', ui_date('not a date', 'en'));

        $card = $this->d($this->build(ListingController::class, $this->rows(1), 'id')->news());
        $this->assertStringContainsString('<time class="card-date" datetime="2024-01-01">01 Januari 2024</time>', $card);
    }

    public function testRelatedHeadingIsSpacedAwayFromTheArticleAndCards(): void
    {
        $css = (string) file_get_contents(FCPATH . 'assets/css/landing.css');

        $this->assertMatchesRegularExpression('#\.news-related \.news-related__title \{[^}]*margin: 0 0 1\.75rem#', $css, 'beats .section h2 { margin: 0 }');
        $this->assertMatchesRegularExpression('#\.news-related \{[^}]*margin-top: 4rem#', $css);
    }
}