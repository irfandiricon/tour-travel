<?php

use CodeIgniter\Test\CIUnitTestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * Property test for the landing page structure across arbitrary data.
 *
 * @internal
 */
final class LandingStructurePropertyTest extends CIUnitTestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'ui']);
    }

    /**
     * Validates: Requirements 3.1, 3.4, 3.5, 3.6, 3.7, 3.9, 4.6, 5.1, 11.7
     */
    public function testLandingStructureIsStableForAnyData(): void
    {
        // Feature: ui-redesign-reference, Property 7: Struktur landing stabil untuk data apa pun
        $this->limitTo(100)->forAll(
            Generators::elements('en', 'id', 'ms'),
            Generators::choose(0, 8),                   // hero slides
            Generators::choose(0, 4),                   // fleet
            Generators::choose(0, 4),                   // gallery
            Generators::choose(0, 4),                   // news
            Generators::choose(0, 4),                   // faq
            Generators::string(),                       // hero title
            Generators::bool()                          // contact whatsapp present
        )->then(function (
            string $lang,
            int $slides,
            int $fleetN,
            int $galleryN,
            int $newsN,
            int $faqN,
            string $title,
            bool $hasWa
        ): void {
            $wa   = $hasWa ? 'https://wa.me/628123456789?text=Hi' : null;
            $data = [
                'lang'         => $lang,
                'languages'    => ['en', 'id', 'ms'],
                'hero'         => ['title' => $title, 'subtitle' => 'Sub'],
                'heroSlides'   => [],
                'about'        => 'About text',
                'galleryTitle' => '',
                'contact'      => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => $wa],
                'fleet'        => [],
                'news'         => [],
                'faq'          => [],
                'gallery'      => [],
            ];
            for ($i = 1; $i <= $slides; $i++) {
                $data['heroSlides'][] = ['image' => "http://localhost/uploads/s{$i}.jpg", 'alt' => "Slide {$i}"];
            }
            for ($i = 1; $i <= $fleetN; $i++) {
                $data['fleet'][] = ['id' => $i, 'name' => "Car {$i}", 'description' => 'd', 'image' => 'x.jpg', 'whatsapp' => null];
            }
            for ($i = 1; $i <= $galleryN; $i++) {
                $data['gallery'][] = ['image' => 'g.jpg', 'caption' => "Cap {$i}"];
            }
            for ($i = 1; $i <= $newsN; $i++) {
                $data['news'][] = ['title' => "News {$i}", 'body' => 'B', 'publish_date' => '2025-01-01 00:00:00'];
            }
            for ($i = 1; $i <= $faqN; $i++) {
                $data['faq'][] = ['question' => "Q{$i}?", 'answer' => 'A'];
            }

            $html = view('landing/index', $data, ['saveData' => false]);

            // Exactly one h1.
            self::assertSame(1, preg_match_all('#<h1[\s>]#', $html));

            // All section ids present.
            foreach (['top', 'about', 'fleet', 'gallery', 'news', 'faq', 'contact'] as $id) {
                self::assertStringContainsString('id="' . $id . '"', $html);
            }

            // Carousel structure.
            $rendered = min($slides, 5);
            self::assertSame($rendered, substr_count($html, 'class="carousel-item'));
            self::assertSame($slides === 0, str_contains($html, 'class="hero"'));

            if ($rendered > 1) {
                self::assertStringContainsString('carousel-control-prev', $html);
                self::assertStringContainsString('carousel-control-next', $html);
                self::assertStringContainsString('carousel-indicators', $html);
                self::assertStringContainsString('data-hero-toggle', $html);
                self::assertSame($rendered, substr_count($html, 'data-bs-slide-to='));
                self::assertStringContainsString('data-bs-target="#hero-carousel"', $html);
                self::assertStringNotContainsString('data-bs-target="#top"', $html);
            } else {
                self::assertStringNotContainsString('carousel-control-prev', $html);
                self::assertStringNotContainsString('carousel-control-next', $html);
                self::assertStringNotContainsString('carousel-indicators', $html);
                self::assertStringNotContainsString('data-hero-toggle', $html);
            }

            // Stats row present iff ui_landing_stats is non-empty.
            $stats = ui_landing_stats(
                ['fleet' => $fleetN, 'gallery' => $galleryN, 'news' => $newsN, 'faq' => $faqN],
                ['fleet' => 'a', 'gallery' => 'b', 'news' => 'c', 'faq' => 'e']
            );
            self::assertSame($stats !== [], str_contains($html, 'class="stats-row"'));
        });
    }
}
