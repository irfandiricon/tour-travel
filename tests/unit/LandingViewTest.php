<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Example tests for landing rendering and the language selector.
 *
 * Validates: Requirements 2.1, 1.8, 5.9, 8.5, 7.7
 *
 * @internal
 */
final class LandingViewTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
    }

    /** @param array<string, mixed> $overrides */
    private function render(array $overrides = []): string
    {
        $base = [
            'lang'         => 'en',
            'languages'    => ['en', 'id', 'ms'],
            'hero'         => ['title' => 'Hero', 'subtitle' => 'Sub'],
            'about'        => 'About text',
            'galleryTitle' => '',
            'contact'      => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => null],
            'fleet'        => [],
            'news'         => [],
            'faq'          => [],
            'gallery'      => [],
        ];

        return view('landing/index', array_replace($base, $overrides), ['saveData' => false]);
    }

    public function testLanguageSelectorHasExactlyEnIdMsLinks(): void
    {
        $html = $this->render();

        $this->assertSame(1, preg_match('#<ul class="lang-selector[^"]*".*?</ul>#s', $html, $m));
        preg_match_all('#href="([^"]*)"#', $m[0], $hrefs);

        $this->assertCount(3, $hrefs[1]);
        $decoded = array_map(static fn ($h) => html_entity_decode($h, ENT_QUOTES | ENT_HTML5), $hrefs[1]);
        $paths   = array_map(static fn ($h) => substr($h, (int) strrpos($h, 'lang/')), $decoded);
        $this->assertSame(['lang/en', 'lang/id', 'lang/ms'], $paths);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function emptyStateProvider(): array
    {
        return [
            'en' => ['en', 'No vehicles are available at the moment.'],
            'id' => ['id', 'Belum ada kendaraan yang tersedia saat ini.'],
            'ms' => ['ms', 'Tiada kenderaan tersedia buat masa ini.'],
        ];
    }

    /** @dataProvider emptyStateProvider */
    public function testEmptyFleetRendersLocalizedEmptyState(string $lang, string $expected): void
    {
        $html = $this->render(['lang' => $lang]);

        $this->assertStringContainsString($expected, $html);
    }

    public function testEmptyNewsAndFaqRenderLocalizedEmptyState(): void
    {
        $en = $this->render(['lang' => 'en']);
        $this->assertStringContainsString('No news or information to show yet.', $en);
        $this->assertStringContainsString('No frequently asked questions yet.', $en);

        $id = $this->render(['lang' => 'id']);
        $this->assertStringContainsString('Belum ada berita atau informasi.', $id);
        $this->assertStringContainsString('Belum ada pertanyaan yang sering diajukan.', $id);

        $ms = $this->render(['lang' => 'ms']);
        $this->assertStringContainsString('Belum ada berita atau maklumat.', $ms);
        $this->assertStringContainsString('Belum ada soalan lazim.', $ms);
    }

    public function testPopulatedSectionsDoNotRenderEmptyState(): void
    {
        $html = $this->render([
            'fleet' => [['id' => 1, 'name' => 'Avanza', 'description' => 'd', 'image' => 'x.jpg', 'whatsapp' => null]],
            'news'  => [['title' => 'T', 'body' => 'B', 'publish_date' => '2025-01-01 00:00:00']],
            'faq'   => [['question' => 'Q?', 'answer' => 'A']],
        ]);

        $this->assertStringNotContainsString('No vehicles are available', $html);
        $this->assertStringNotContainsString('No news or information to show yet.', $html);
        $this->assertStringNotContainsString('No frequently asked questions yet.', $html);
    }

    public function testBookingButtonAbsentWhenWhatsappLinkNull(): void
    {
        $html = $this->render([
            'fleet' => [['id' => 1, 'name' => 'Avanza', 'description' => '', 'image' => 'x.jpg', 'whatsapp' => null]],
        ]);

        $this->assertStringNotContainsString('Book via WhatsApp', $html);
        $this->assertStringNotContainsString('wa.me', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
    }

    /** @return list<array{image: string, alt: string}> */
    private function slides(int $n): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $out[] = ['image' => "http://localhost/uploads/s{$i}.jpg", 'alt' => "Slide alt {$i}"];
        }

        return $out;
    }

    /** @return array<string, array{0: int, 1: int}> count => [given, rendered] */
    public static function carouselProvider(): array
    {
        return ['0 items' => [0, 0], '1 item' => [1, 1], '3 items' => [3, 3], '7 items (capped)' => [7, 5]];
    }

    /** @dataProvider carouselProvider */
    public function testCarouselRendersExpectedSlidesAndControls(int $given, int $rendered): void
    {
        $html = $this->render(['heroSlides' => $this->slides($given)]);

        $this->assertSame($rendered, substr_count($html, 'class="carousel-item'));

        if ($rendered === 0) {
            $this->assertStringNotContainsString('data-bs-ride="carousel"', $html);
            $this->assertStringNotContainsString('carousel-control-prev', $html);
            $this->assertStringContainsString('class="hero"', $html);
        } else {
            $this->assertStringContainsString('data-bs-ride="carousel"', $html);
            $this->assertStringContainsString('data-bs-interval="5000"', $html);
        }

        if ($rendered > 1) {
            $this->assertStringContainsString('carousel-control-prev', $html);
            $this->assertStringContainsString('carousel-control-next', $html);
            $this->assertSame($rendered, substr_count($html, 'data-bs-slide-to='));
        } else {
            $this->assertStringNotContainsString('carousel-control-prev', $html);
            $this->assertStringNotContainsString('carousel-control-next', $html);
            $this->assertStringNotContainsString('carousel-indicators', $html);
        }
    }

    /** @dataProvider carouselProvider */
    public function testExactlyOneH1RegardlessOfSlideCount(int $given): void
    {
        $html = $this->render(['heroSlides' => $this->slides($given)]);

        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
    }

    public function testCarouselControlsTargetHeroCarousel(): void
    {
        $html = $this->render(['heroSlides' => $this->slides(3)]);

        $this->assertStringContainsString('id="hero-carousel"', $html);
        $this->assertSame(3, substr_count($html, 'data-bs-slide-to='));
        $this->assertSame(
            substr_count($html, 'data-bs-target="#hero-carousel"'),
            3 + 2,
            'indicators + prev/next must target #hero-carousel'
        );
        $this->assertStringNotContainsString('data-bs-target="#top"', $html);
        $this->assertStringContainsString('data-hero-toggle', $html);
    }

    public function testHeroToggleAbsentWithSingleOrNoSlide(): void
    {
        $this->assertStringNotContainsString('data-hero-toggle', $this->render(['heroSlides' => $this->slides(1)]));
        $this->assertStringNotContainsString('data-hero-toggle', $this->render());
    }

    public function testHeroTitleHighlightsLastWordInsideH1(): void
    {
        $html = $this->render(['hero' => ['title' => 'Explore the Island', 'subtitle' => '']]);

        $this->assertSame(1, preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $m));
        $this->assertStringContainsString('<mark class="hl">Island</mark>', $m[1]);
    }

    public function testStatsRowAbsentWhenAllDataEmpty(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('stats-row', $html);
        $this->assertStringNotContainsString('stat-card', $html);
    }

    public function testStatsRowPresentWhenDataExists(): void
    {
        $html = $this->render([
            'fleet' => [['id' => 1, 'name' => 'Avanza', 'description' => '', 'image' => 'x.jpg', 'whatsapp' => null]],
        ]);

        $this->assertStringContainsString('stats-row', $html);
        $this->assertStringContainsString('stat-card--dark', $html);
    }

    public function testReadyToBookChipOnlyWhenWhatsappPresent(): void
    {
        $item = ['id' => 1, 'name' => 'Avanza', 'description' => '', 'image' => 'x.jpg'];

        $with = $this->render(['fleet' => [$item + ['whatsapp' => 'https://wa.me/628123456789?text=Hi']]]);
        $this->assertStringContainsString('Ready to book', $with);

        $without = $this->render(['fleet' => [$item + ['whatsapp' => null]]]);
        $this->assertStringNotContainsString('Ready to book', $without);
    }

    public function testFaqRendersAsAccordion(): void
    {
        $html = $this->render(['faq' => [['question' => 'Q1?', 'answer' => 'A1'], ['question' => 'Q2?', 'answer' => 'A2']]]);

        $this->assertStringContainsString('class="accordion"', $html);
        $this->assertSame(2, substr_count($html, 'class="accordion-item"'));
        $this->assertSame(2, substr_count($html, 'data-bs-toggle="collapse" data-bs-target="#faq-a-'));
    }

    public function testFloatingWhatsappButtonVisibleOnlyWhenLinkExists(): void
    {
        $link = 'https://wa.me/628123456789?text=Hi';

        $with = $this->render(['contact' => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => $link]]);
        $this->assertStringContainsString('class="wa-float"', $with);

        $without = $this->render();
        $this->assertStringNotContainsString('wa-float', $without);
    }

    public function testWhatsappLinkIsAttributeEscaped(): void
    {
        $link = 'https://wa.me/628123456789?text=a&b="x"';
        $html = $this->render([
            'fleet' => [['id' => 1, 'name' => 'Avanza', 'description' => '', 'image' => 'x.jpg', 'whatsapp' => $link]],
        ]);

        $this->assertStringNotContainsString('"x"', $html);
        $this->assertStringContainsString('&quot;x&quot;', $html);
    }

    public function testBookingButtonPresentWhenWhatsappLinkNonNull(): void
    {
        $link = 'https://wa.me/628123456789?text=Hello';
        $html = $this->render([
            'fleet' => [['id' => 1, 'name' => 'Avanza', 'description' => '', 'image' => 'x.jpg', 'whatsapp' => $link]],
        ]);

        $this->assertStringContainsString('Book via WhatsApp', $html);
        $this->assertStringContainsString('https://wa.me/628123456789', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
    }

    public function testFaqAccordionIsDrivenOnlyByBootstrap(): void
    {
        // A second hand-written click handler on .faq-question fought Bootstrap's collapse and
        // left answers closed after a click. Bootstrap's data-bs-toggle must be the only driver.
        $js = (string) file_get_contents(FCPATH . 'assets/js/landing.js');

        $this->assertDoesNotMatchRegularExpression('#querySelectorAll\(\s*[\'"]\.faq-question#', $js);
        $this->assertStringNotContainsString('panel.hidden', $js);
    }

    public function testHeroTitleIsBoldColourfulAndShadowed(): void
    {
        $css    = (string) file_get_contents(FCPATH . 'assets/css/landing.css');
        $tokens = (string) file_get_contents(FCPATH . 'assets/css/tokens.css');

        $this->assertMatchesRegularExpression('/\.hero-section \.hero-title \{[^}]*font-weight: 800;[^}]*filter: drop-shadow/s', $css);
        $this->assertStringContainsString('background: linear-gradient(100deg, var(--lp-navy) 0%, var(--lp-navy) 35%, var(--lp-blue) 100%);', $css);
        $this->assertStringContainsString('-webkit-background-clip: text; background-clip: text;', $css);
        $this->assertStringContainsString('.hero-section .hero-title .hl { -webkit-text-fill-color: var(--lp-orange-deep); }', $css, 'last word drawn above its marker');
        $this->assertMatchesRegularExpression('/\.hero-section \.hero-title \.hl::after \{[^}]*z-index: -1;[^}]*background: var\(--lp-lime\)/s', $css);
        $this->assertStringContainsString('@media (forced-colors: active)', $css);
        $this->assertMatchesRegularExpression('/\.about-section \.about-title \{/', $css, 'About title keeps its own style');
        foreach (['--lp-navy: #0b2e5c;', '--lp-blue: #1f5fd0;', '--lp-orange-deep: #b8390a;', '--lp-orange: #d9480f;', '--lp-hero-sub: #3a4454;'] as $token) {
            $this->assertStringContainsString($token, $tokens);
        }
    }

    public function testSectionHeadingsRevealOnScrollWithSafeFallbacks(): void
    {
        $layout = (string) file_get_contents(APPPATH . 'Views/layouts/public.php');
        $js     = (string) file_get_contents(FCPATH . 'assets/js/landing.js');
        $css    = (string) file_get_contents(FCPATH . 'assets/css/landing.css');

        // Hidden state only when the browser can animate and the visitor allows motion; undone after 3 s.
        $this->assertStringContainsString("if (!('IntersectionObserver' in w) || (w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches)) { return; }", $layout);
        $this->assertStringContainsString("if (!w.kimigoRevealReady) { d.documentElement.classList.remove('js-reveal'); } }, 3000);", $layout);
        $this->assertLessThan(strpos($layout, "renderSection('content')"), strpos($layout, "classList.add('js-reveal')"), 'set in <head>, before content paints');

        $this->assertStringContainsString("document.querySelectorAll('.section .section-head, .about-section .about-eyebrow, .about-section .about-title, .contact-card > h2')", $js);
        $this->assertStringContainsString('io.unobserve(entry.target); // animate once', $js);

        $this->assertStringContainsString('.js-reveal .reveal {', $css);
        $this->assertStringContainsString('.js-reveal .reveal.is-visible { opacity: 1; transform: none; }', $css);
        $this->assertDoesNotMatchRegularExpression('/(^|\n)\.reveal \{[^}]*opacity: 0/', $css, 'never hidden without html.js-reveal');
        $this->assertMatchesRegularExpression('/\.section-head h2::after \{[^}]*linear-gradient\(90deg, var\(--lp-blue\), var\(--lp-orange\)\)/s', $css);
        $this->assertMatchesRegularExpression('/@media \(prefers-reduced-motion: reduce\) \{\s*\.js-reveal \.reveal, \.js-reveal \.section-head\.reveal \.section-sub \{ opacity: 1; transform: none; transition: none; \}/', $css);
    }

    public function testSectionContentAlsoRevealsOnScrollAndCleansUp(): void
    {
        $js  = (string) file_get_contents(FCPATH . 'assets/js/landing.js');
        $css = (string) file_get_contents(FCPATH . 'assets/css/landing.css');

        $this->assertStringContainsString("document.querySelectorAll('.fleet-rail, .gallery-grid, .news-rail, #faq-accordion, .contact-box, .about-content')", $js);
        $this->assertStringContainsString("if (child.classList.contains('reveal')) {", $js, 'headings keep their own animation');
        $this->assertStringContainsString("item.classList.remove('reveal-item', 'is-revealed');", $js, 'classes removed afterwards so hover effects work');
        $this->assertStringContainsString("{ rootMargin: '0px 0px -10% 0px', threshold: 0 }", $js, 'also fires for blocks taller than the screen');

        $this->assertStringContainsString('.js-reveal .reveal-item { opacity: 0; transform: translate3d(0, 24px, 0); }', $css);
        $this->assertStringContainsString('animation-delay: calc(var(--reveal-i, 0) * 90ms + .15s);', $css);
        $this->assertDoesNotMatchRegularExpression('/(^|\n)\.reveal-item \{/', $css, 'never hidden without html.js-reveal');
        $this->assertMatchesRegularExpression('/@media \(prefers-reduced-motion: reduce\) \{\s*\.js-reveal \.reveal-item, \.js-reveal \.reveal-item\.is-revealed \{ opacity: 1; transform: none; animation: none; \}/', $css);
        $this->assertStringContainsString('@media print {', $css);
    }
}