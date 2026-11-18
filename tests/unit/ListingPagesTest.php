<?php

use App\Controllers\LandingController;
use App\Controllers\LanguageController;
use App\Controllers\ListingController;
use App\Controllers\PublicBaseController;
use App\Libraries\SeoMetaBuilder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Landing previews ("View more") and the Fleet / Gallery / News & Information pages.
 * DB-free: the Api*Controller proxies are replaced with stubs.
 *
 * @internal
 */
final class ListingPagesTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'ui']);
        service('request')->setGlobal('get', []);
    }

    private function stub(string $method, $result): object
    {
        return new class ($method, $result) {
            public function __construct(private string $m, private $r)
            {
            }

            public function __call($name, $args)
            {
                if ($name !== $this->m) {
                    throw new BadMethodCallException($name);
                }

                return $this->r;
            }
        };
    }

    private function ok(array $data): array
    {
        return ['status' => 200, 'message' => '', 'data' => $data, 'total' => count($data)];
    }

    /** @return list<array<string,mixed>> */
    private function fleetRows(int $n): array
    {
        $rows = [];
        for ($i = 1; $i <= $n; $i++) {
            $rows[] = ['id' => $i, 'name_en' => "Car {$i}", 'name_id' => "Mobil {$i}", 'description_en' => "Desc {$i}", 'image_path' => "uploads/c{$i}.jpg", 'status' => 'published'];
        }

        return $rows;
    }

    /** @return list<array<string,mixed>> */
    private function newsRows(int $n): array
    {
        $rows = [];
        for ($i = 1; $i <= $n; $i++) {
            $rows[] = ['status' => 'published', 'publish_date' => sprintf('2024-01-%02d 00:00:00', $i), 'title_en' => "News {$i}", 'title_id' => "Berita {$i}", 'body_en' => "Body {$i}"];
        }

        return $rows;
    }

    /** @return array<string, object> */
    private function proxies(int $fleet = 0, int $gallery = 0, int $news = 0, string $number = '+628123456789'): array
    {
        $galleryRows = [];
        for ($i = 1; $i <= $gallery; $i++) {
            $galleryRows[] = ['image_path' => "uploads/g{$i}.jpg", 'caption_en' => "Shot {$i}", 'sort_order' => $i, 'status' => 'published'];
        }

        return [
            'apiSiteConfig'  => $this->stub('apiGetConfig', $this->ok(['whatsapp_number' => $number, 'hero_title_en' => 'Hero', 'site_name_en' => 'Kimigo Travel'])),
            'apiFleet'       => $this->stub('apiListActive', $this->ok($this->fleetRows($fleet))),
            'apiGallery'     => $this->stub('apiListActive', $this->ok($galleryRows)),
            'apiNews'        => $this->stub('apiListActive', $this->ok($this->newsRows($news))),
            'apiFaq'         => $this->stub('apiListActive', $this->ok([])),
        ];
    }

    private function build(string $class, array $proxies, string $lang = 'en')
    {
        $ref = new ReflectionClass($class);
        $c   = $ref->newInstanceWithoutConstructor();

        foreach ($proxies as $prop => $stub) {
            if ($ref->hasProperty($prop)) {
                $p = $ref->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($c, $stub);
            }
        }
        $base = new ReflectionClass(PublicBaseController::class);
        $l    = $base->getProperty('lang');
        $l->setAccessible(true);
        $l->setValue($c, $lang);
        $r = $base->getProperty('request');
        $r->setAccessible(true);
        $r->setValue($c, service('request'));

        return $c;
    }

    private function landing(array $proxies, string $lang = 'en'): string
    {
        return $this->build(LandingController::class, $proxies, $lang)->index();
    }

    private function page(string $kind, array $proxies, string $lang = 'en', int $page = 1): string
    {
        service('request')->setGlobal('get', $page > 1 ? ['page' => (string) $page] : []);

        return $this->build(ListingController::class, $proxies, $lang)->{$kind}();
    }

    private function d(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
    }

    // ------------------------------------------------------------ landing preview

    public function testLandingShowsOnlyAPreviewOfEachSection(): void
    {
        $html = $this->landing($this->proxies(8, 10, 12));

        $this->assertSame(LandingController::SECTION_LIMITS['fleet'], substr_count($html, 'class="fleet-card"'));
        $this->assertSame(LandingController::SECTION_LIMITS['gallery'], substr_count($html, 'class="gallery-item"'));
        $this->assertSame(LandingController::SECTION_LIMITS['news'], substr_count($html, 'class="news-card"'));
    }

    public function testStatsUseTheRealTotalsNotThePreviewSize(): void
    {
        $html = $this->landing($this->proxies(8, 10, 8));

        $this->assertMatchesRegularExpression('#stat-card__value">8</div>\s*<div class="stat-card__label">Vehicles in our fleet#', $html);
        $this->assertStringContainsString('8 vehicles ready to go', $html);
    }

    public function testEachPreviewHasAViewMoreButtonToItsPage(): void
    {
        $html = $this->d($this->landing($this->proxies(2, 2, 2)));

        foreach (['fleet', 'gallery', 'news'] as $slug) {
            $this->assertMatchesRegularExpression('#<a class="btn-more" href="[^"]*/' . $slug . '">View more#', $html, $slug);
        }
        $this->assertSame(3, substr_count($html, 'class="btn-more"'));
    }

    public function testViewMoreButtonLabelFollowsTheLanguage(): void
    {
        $this->assertStringContainsString('Lihat Selengkapnya', $this->landing($this->proxies(2, 2, 2), 'id'));
        $this->assertStringContainsString('Lihat Lagi', $this->landing($this->proxies(2, 2, 2), 'ms'));
    }

    public function testNoViewMoreButtonForAnEmptySection(): void
    {
        $html = $this->landing($this->proxies(0, 0, 0));

        $this->assertStringNotContainsString('btn-more', $html);
    }

    public function testNavLinksOfThePageSectionsPointToTheirPages(): void
    {
        $html = $this->d($this->landing($this->proxies(1, 1, 1)));

        // On the landing page every menu item is an in-page anchor to its section.
        foreach (['about', 'fleet', 'gallery', 'news', 'faq', 'contact'] as $section) {
            $this->assertMatchesRegularExpression('#<a class="nav-link" href="\#' . $section . '">#', $html, $section);
            $this->assertStringContainsString('<section id="' . $section . '"', $html, $section);
        }
        $this->assertStringNotContainsString('aria-current="page" href', $html);
    }

    // ------------------------------------------------------------------- pages

    /** @return array<string, array{0:string,1:string}> */
    public static function pageProvider(): array
    {
        return [
            'fleet'       => ['fleet', 'Our Fleet'],
            'gallery'     => ['gallery', 'Gallery'],
            'news'        => ['news', 'News & Information'],
        ];
    }

    /** @dataProvider pageProvider */
    public function testEveryPageHasOneH1AndBackToHome(string $kind, string $title): void
    {
        $html = $this->page($kind, $this->proxies(2, 2, 2));

        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
        $this->assertStringContainsString('<h1 id="page-title" class="page-title">' . esc($title) . '</h1>', $html);
        $this->assertStringContainsString('Back to home', $html);
        $this->assertStringContainsString('breadcrumb', $html);
    }

    /** @dataProvider pageProvider */
    public function testEveryPageMarksItsOwnNavLinkAsCurrent(string $kind): void
    {
        $html = $this->d($this->page($kind, $this->proxies(1, 1, 1)));

        // Highlighted, but it leads to the landing section, so it is not marked as the current page.
        $this->assertMatchesRegularExpression('#<a class="nav-link active" href="https?://[^"]*/\#' . $kind . '">#', $html);
        $this->assertSame(1, substr_count($html, 'class="nav-link active"'));
        $this->assertStringNotContainsString('aria-current="page" href', $html);
    }

    /** @dataProvider pageProvider */
    public function testAnchorMenusOnAPageLeadBackToTheLanding(string $kind): void
    {
        $html = $this->d($this->page($kind, $this->proxies(1, 1, 1)));

        foreach (['about', 'fleet', 'gallery', 'news', 'faq', 'contact'] as $anchor) {
            $this->assertMatchesRegularExpression('#<a class="nav-link( active)?" href="https?://[^"]*/\#' . $anchor . '"#', $html, $anchor);
        }
        $this->assertStringContainsString('lang/en?return=' . $kind, $html);
    }

    /** @dataProvider pageProvider */
    public function testEveryPageHasSeoHead(string $kind, string $title): void
    {
        $html = $this->d($this->page($kind, $this->proxies(1, 1, 1)));

        $this->assertStringContainsString('<title>' . $title . ' | Kimigo Travel</title>', $html);
        $this->assertMatchesRegularExpression('#<link rel="canonical" href="https?://[^"]*/' . $kind . '">#', $html);
        $this->assertSame(4, preg_match_all('#<link rel="alternate" hreflang=#', $html));
        $this->assertStringContainsString('BreadcrumbList', $html);
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $html);
    }

    /** @dataProvider pageProvider */
    public function testEmptyPageShowsEmptyStateAndNoPagination(string $kind): void
    {
        $html = $this->page($kind, $this->proxies(0, 0, 0));

        $this->assertStringContainsString('empty-state', $html);
        $this->assertStringNotContainsString('listing-pagination', $html);
    }

    public function testFleetPageShowsEveryVehicleAcrossPages(): void
    {
        $proxies = $this->proxies(20);
        $per     = ListingController::PER_PAGE['fleet'];

        $this->assertSame($per, substr_count($this->page('fleet', $proxies), 'class="fleet-card"'));
        $this->assertSame($per, substr_count($this->page('fleet', $proxies, 'en', 2), 'class="fleet-card"'));
        $this->assertSame(20 - 2 * $per, substr_count($this->page('fleet', $proxies, 'en', 3), 'class="fleet-card"'));
    }

    public function testPageNumberIsClampedToTheValidRange(): void
    {
        $proxies = $this->proxies(20);

        $last = $this->page('fleet', $proxies, 'en', 99);
        $this->assertStringContainsString('Page 3 of 3', $last);
        $this->assertStringContainsString('Car 20', $last);

        service('request')->setGlobal('get', ['page' => '-5']);
        $first = $this->build(ListingController::class, $proxies)->fleet();
        $this->assertStringContainsString('Page 1 of 3', $first);
    }

    public function testPaginationLinksAndDisabledEnds(): void
    {
        $html = $this->d($this->page('fleet', $this->proxies(20), 'en', 2));

        $this->assertMatchesRegularExpression('#rel="prev"#', $html);
        $this->assertMatchesRegularExpression('#rel="next"#', $html);
        $this->assertMatchesRegularExpression('#href="[^"]*/fleet\?page=3"#', $html);
        $this->assertMatchesRegularExpression('#<a class="page-link" href="[^"]*/fleet"[^>]*rel="prev"#', $html);

        $first = $this->page('fleet', $this->proxies(20), 'en', 1);
        $this->assertMatchesRegularExpression('#<li class="page-item disabled">\s*<span class="page-link">Previous</span>#', $first);
    }

    public function testOnlyOnePageMeansNoPagination(): void
    {
        $this->assertStringNotContainsString('listing-pagination', $this->page('fleet', $this->proxies(3)));
    }

    public function testNewsPageListsOnlyReachedPublishedNewsNewestFirst(): void
    {
        $rows = $this->newsRows(3);
        $rows[] = ['status' => 'draft', 'publish_date' => '2024-02-01 00:00:00', 'title_en' => 'Draft news', 'body_en' => 'x'];
        $rows[] = ['status' => 'published', 'publish_date' => '2999-01-01 00:00:00', 'title_en' => 'Future news', 'body_en' => 'x'];
        $proxies = $this->proxies();
        $proxies['apiNews'] = $this->stub('apiListActive', $this->ok($rows));

        $html = $this->page('news', $proxies);

        $this->assertStringNotContainsString('Draft news', $html);
        $this->assertStringNotContainsString('Future news', $html);
        $this->assertLessThan(strpos($html, 'News 2'), strpos($html, 'News 3'), 'newest first');
        $this->assertLessThan(strpos($html, 'News 1'), strpos($html, 'News 2'));
    }

    public function testPagesAreLocalized(): void
    {
        $this->assertStringContainsString('<h1 id="page-title" class="page-title">Berita &amp; Informasi</h1>', $this->page('news', $this->proxies(0, 0, 2), 'id'));
        $this->assertStringContainsString('Berita 1', $this->page('news', $this->proxies(0, 0, 2), 'id'));
        $this->assertStringContainsString('<h1 id="page-title" class="page-title">Armada Kami</h1>', $this->page('fleet', $this->proxies(2), 'ms'));
        $this->assertStringContainsString('Kembali ke laman utama', $this->page('gallery', $this->proxies(0, 2), 'ms'));
    }

    public function testBookingButtonOnFleetPageOnlyWithAWhatsappNumber(): void
    {
        $with = $this->d($this->page('fleet', $this->proxies(2)));
        $this->assertSame(2, substr_count($with, 'data-track-booking='));
        $this->assertMatchesRegularExpression('#https://wa\.me/628123456789\?text=#', $with);

        $without = $this->page('fleet', $this->proxies(2, 0, 0, ''));
        $this->assertStringNotContainsString('data-track-booking=', $without);
        $this->assertStringNotContainsString('wa.me', $this->d($without));
    }

    public function testGalleryPageUsesTheCmsGalleryTitle(): void
    {
        $proxies = $this->proxies(0, 2);
        $proxies['apiSiteConfig'] = $this->stub('apiGetConfig', $this->ok(['gallery_title_en' => 'Our Moments', 'site_name_en' => 'Kimigo Travel']));

        $this->assertStringContainsString('<h1 id="page-title" class="page-title">Our Moments</h1>', $this->page('gallery', $proxies));
    }

    public function testFailedProxiesDoNotBreakAPage(): void
    {
        $down = $this->stub('apiListActive', ['status' => 502, 'message' => 'x', 'data' => [], 'total' => 0]);
        $html = $this->page('fleet', ['apiFleet' => $down, 'apiSiteConfig' => $this->stub('apiGetConfig', ['status' => 502, 'data' => []])]);

        $this->assertStringContainsString('empty-state', $html);
        $this->assertStringContainsString('<h1 id="page-title"', $html);
    }

    // ------------------------------------------------- Information merged into News

    public function testOldInformationUrlRedirectsPermanentlyToNews(): void
    {
        $routes = service('routes', false);
        $routes->loadRoutes();

        $this->assertTrue($routes->isRedirect('information'), '/information is a redirect');
        $this->assertSame(301, $routes->getRedirectCode('information'));
        $this->assertFalse(method_exists(ListingController::class, 'information'), 'there is no separate Information page any more');
    }

    public function testThereIsNoInformationSectionOrMenuAnyMore(): void
    {
        $html = $this->d($this->landing($this->proxies(2, 2, 2)));

        $this->assertStringNotContainsString('id="information"', $html);
        $this->assertStringNotContainsString('info-card', $html);
        $this->assertDoesNotMatchRegularExpression('#<a class="nav-link"[^>]*href="[^"]*(/|\#)information"#', $html);
        // The single News & Information menu stays.
        $this->assertMatchesRegularExpression('#<a class="nav-link"[^>]*href="[^"]*\#news"[^>]*>News & Information</a>#', $html);
    }

    public function testNewsAndInformationMenuIsLocalizedInEveryLanguage(): void
    {
        $this->assertStringContainsString('>Berita & Informasi</a>', $this->d($this->landing($this->proxies(1, 1, 1), 'id')));
        $this->assertStringContainsString('>Berita & Maklumat</a>', $this->d($this->landing($this->proxies(1, 1, 1), 'ms')));
    }

    public function testLanguageSwitchNoLongerReturnsToTheOldInformationPage(): void
    {
        service('request')->setGlobal('get', ['return' => 'information']);

        $this->assertSame(site_url('/'), (new LanguageController())->set('id')->getHeaderLine('Location'));
    }
    // --------------------------------------------------------------------- SEO

    public function testBuildPageProducesAbsoluteCanonicalAlternatesAndBreadcrumb(): void
    {
        $seo = SeoMetaBuilder::buildPage(['brand' => 'Kimigo Travel', 'gallery' => []], [], 'http://x.test/app/', 'id', 'news', 'Berita', 'Kabar terbaru', 'Beranda', 2);

        $this->assertSame('Berita - 2 | Kimigo Travel', $seo['title']);
        $this->assertSame('http://x.test/app/news?page=2&lang=id', $seo['canonical']);
        $this->assertCount(4, $seo['alternates']);
        $this->assertSame('http://x.test/app/news?page=2', $seo['alternates'][0]['href']);
        $this->assertSame('x-default', $seo['alternates'][3]['hreflang']);
        $this->assertSame('id_ID', $seo['og']['og:locale']);
        $this->assertStringEndsWith('assets/img/og-default.jpg', $seo['og']['og:image']);

        $json = json_decode(SeoMetaBuilder::encodeJsonLd($seo['jsonld']), true);
        $this->assertSame('BreadcrumbList', $json['@type']);
        $this->assertSame('Beranda', $json['itemListElement'][0]['name']);
        $this->assertSame('http://x.test/app/news?lang=id', $json['itemListElement'][1]['item']);
    }

    public function testBuildPageUsesFirstRealImageForSocialSharing(): void
    {
        $seo = SeoMetaBuilder::buildPage(['brand' => 'B', 'gallery' => [['image' => 'uploads/fleet/a.jpg']]], [], 'http://x.test/', 'en', 'fleet', 'Our Fleet', 'd');

        $this->assertSame('http://x.test/uploads/fleet/a.jpg', $seo['og']['og:image']);
    }

    public function testLandingUrlsAreUnchangedByTheNewUrlParameters(): void
    {
        $this->assertSame('http://x.test/', SeoMetaBuilder::urlFor('http://x.test', 'en'));
        $this->assertSame('http://x.test/?lang=ms', SeoMetaBuilder::urlFor('http://x.test/', 'ms'));
        $this->assertSame('http://x.test/', SeoMetaBuilder::alternates('http://x.test')[0]['href']);
    }

    // ------------------------------------------------------ labels and language

    public function testPageLabelsHaveTheSameKeysInEveryLanguage(): void
    {
        $en = ui_page_labels('en');
        foreach (['id', 'ms'] as $lang) {
            $l = ui_page_labels($lang);
            $this->assertSame(array_keys($en), array_keys($l), $lang);
            foreach (['fleet', 'gallery', 'news'] as $kind) {
                $this->assertSame(array_keys($en['pages'][$kind]), array_keys($l['pages'][$kind]), "$lang $kind");
                foreach ($l['pages'][$kind] as $key => $value) {
                    $this->assertNotSame('', trim($value), "$lang $kind $key");
                }
            }
        }
        $this->assertSame($en, ui_page_labels('xx'), 'unknown language falls back to English');
    }

    public function testLanguageSwitchReturnsToTheSamePageOnlyForKnownPages(): void
    {
        $controller = new LanguageController();

        service('request')->setGlobal('get', ['return' => 'news']);
        $this->assertSame(site_url('news'), $controller->set('id')->getHeaderLine('Location'));

        foreach (['//evil.test', 'http://evil.test', 'admin', '../x', ''] as $bad) {
            service('request')->setGlobal('get', ['return' => $bad]);
            $this->assertSame(site_url('/'), $controller->set('id')->getHeaderLine('Location'), $bad);
        }

        service('request')->setGlobal('get', []);
        $this->assertSame(site_url('/'), $controller->set('id')->getHeaderLine('Location'));
    }

    public function testMobileMenuClosesAfterChoosingASection(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/landing.js');

        $this->assertStringContainsString("var menu = document.getElementById('site-nav');", $js);
        $this->assertStringContainsString("window.bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();", $js);
    }

    public function testNavbarBrandIsTheLogoImage(): void
    {
        [$w, $h] = getimagesize(FCPATH . 'uploads/brand/kimigo-travel-navbar.png');
        foreach ([$this->landing($this->proxies(1, 1, 1)), $this->page('news', $this->proxies(1, 1, 1))] as $html) {
            $html = $this->d($html);
            $this->assertMatchesRegularExpression('#<source srcset="https?://[^"]*/uploads/brand/kimigo-travel-navbar\.webp\?v=\d+" type="image/webp">#', $html);
            $this->assertMatchesRegularExpression('#<img class="brand-logo" src="https?://[^"]*/uploads/brand/kimigo-travel-navbar\.png\?v=\d+" alt="Kimigo Travel"\s+width="' . $w . '" height="' . $h . '">#', $html);
            $this->assertStringNotContainsString('kimigo-travel-logo.webp', $html, 'the old logo is no longer served');
            $this->assertStringNotContainsString('class="brand-text"', $html, 'the image replaces the text brand');
        }
        $this->assertFileExists(FCPATH . 'uploads/brand/kimigo-travel.png');
        $this->assertGreaterThanOrEqual(filemtime(FCPATH . 'uploads/brand/kimigo-travel.png'), filemtime(FCPATH . 'uploads/brand/kimigo-travel-navbar.png'), 'navbar copy made from the current logo');
    }

    public function testANewerSourceLogoIsShownUntilTheNavbarCopyIsRemade(): void
    {
        $src = (string) file_get_contents(APPPATH . 'Views/layouts/public.php');
        $this->assertStringContainsString("filemtime(\$brandDir . 'kimigo-travel-navbar.png') >= \$srcTime;", $src);
        $this->assertStringContainsString("\$pngName  = \$useSmall ? 'kimigo-travel-navbar.png' : 'kimigo-travel.png';", $src);
    }
    public function testEveryLandingSectionHasItsOwnBackground(): void
    {
        $html = $this->landing($this->proxies(1, 1, 1));
        $css  = (string) file_get_contents(FCPATH . 'assets/css/landing.css');

        $expected = ['about' => 'white', 'fleet' => 'sky', 'gallery' => 'sand', 'news' => 'mist', 'faq' => 'mint', 'contact' => 'sky-deep'];
        foreach ($expected as $id => $bg) {
            $this->assertMatchesRegularExpression('#<section id="' . $id . '" class="section[^"]* section--' . $bg . '"#', $html, $id);
            $this->assertStringContainsString('.section--' . $bg . ' {', $css, $bg);
        }
        $this->assertStringContainsString('section--peach', (string) file_get_contents(APPPATH . 'Views/landing/index.php'), 'reviews (only shown when published)');
        $this->assertSame(count($expected) + 1, count(array_unique(array_merge(array_values($expected), ['peach']))), 'no two sections share a background');
        $this->assertStringContainsString('.section { padding: var(--space-section) 0; }', $css, 'top and bottom padding so each background has room');
        $this->assertDoesNotMatchRegularExpression('/\.section--[a-z-]+ \{[^}]*#[0-9a-f]{3,6}/i', $css, 'colours come from tokens.css');
    }
}