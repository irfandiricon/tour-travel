<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Hover zoom + image popup (lightbox) markup on the landing page and listing pages.
 *
 * @internal
 */
final class LightboxTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'ui']);
    }

    private function imgUrl(): callable
    {
        return static fn (string $p): string => preg_match('#^https?://#i', $p) === 1 ? $p : base_url(ltrim($p, '/'));
    }

    /** @param array<string,mixed> $o */
    private function fleetCard(array $o = [], array $t = []): string
    {
        $item = array_merge(['id' => 1, 'name' => 'Toyota Avanza', 'description' => 'MPV', 'image' => 'uploads/fleet/a.jpg', 'whatsapp' => null], $o);

        return ui_partial('fleet_card', [
            'item' => $item, 't' => $t + ['book' => 'Book', 'readyToBook' => 'Ready', 'imageAlt' => 'Vehicle image', 'zoomImage' => 'View larger image'],
            'imgUrl' => $this->imgUrl(), 'placeholder' => base_url('assets/img/fleet-placeholder.svg'), 'trackUrl' => '',
        ]);
    }

    private function d(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
    }

    /** @param array<string,mixed> $o */
    private function landing(array $o = []): string
    {
        $base = [
            'lang' => 'en', 'languages' => ['en', 'id', 'ms'], 'hero' => ['title' => 'Hero', 'subtitle' => 'Sub'],
            'about' => 'About text', 'galleryTitle' => '', 'brand' => 'Kimigo Travel',
            'contact' => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => null],
            'fleet' => [], 'news' => [], 'faq' => [], 'gallery' => [],
        ];

        return view('landing/index', array_replace($base, $o), ['saveData' => false]);
    }

    // ---------------------------------------------------------------- partials

    public function testFleetCardWithPictureIsZoomable(): void
    {
        $html = $this->d($this->fleetCard());

        $this->assertStringContainsString('<button type="button" class="zoomable" data-lightbox data-lightbox-group="fleet"', $html);
        $this->assertMatchesRegularExpression('#data-lightbox-src="https?://[^"]*/uploads/fleet/a\.jpg"#', $html);
        $this->assertStringContainsString('data-lightbox-caption="Toyota Avanza"', $html);
        $this->assertStringContainsString('aria-label="View larger image: Toyota Avanza"', $html);
        $this->assertStringContainsString('class="bi bi-zoom-in zoom-icon"', $html);
        $this->assertSame(1, substr_count($html, '</button>'));
    }

    public function testFleetCardWithoutPictureIsNotZoomable(): void
    {
        foreach (['', 'assets/img/fleet-placeholder.svg'] as $image) {
            $html = $this->fleetCard(['image' => $image]);

            $this->assertStringNotContainsString('data-lightbox', $html, "image '{$image}'");
            $this->assertStringNotContainsString('zoomable', $html);
            $this->assertStringContainsString('class="fleet-img"', $html, 'the picture itself is still shown');
        }
    }

    public function testBookingButtonStillWorksInsideAZoomableCard(): void
    {
        $html = $this->d($this->fleetCard(['whatsapp' => 'https://wa.me/628123456789?text=Hi']));

        $this->assertStringContainsString('data-track-booking=', $html);
        $this->assertStringContainsString('href="https://wa.me/628123456789?text=Hi"', $html);
        $this->assertStringContainsString('Ready', $html);
    }

    public function testCaptionsAreEscapedInDataAttributes(): void
    {
        $hostile = '"><script>alert(1)</script>';
        $html    = $this->fleetCard(['name' => $hostile]);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('"><script', $html);
        $this->assertStringContainsString('&quot;', $html, 'the quote in the caption is escaped');
    }

    public function testGalleryItemIsZoomableAndWorksWithoutLabels(): void
    {
        $html = $this->d(ui_partial('gallery_item', [
            'g' => ['image' => 'uploads/gallery/g.png', 'caption' => 'Sunset'],
            'imgUrl' => $this->imgUrl(), 'placeholder' => base_url('assets/img/fleet-placeholder.svg'),
        ]));

        $this->assertStringContainsString('data-lightbox-group="gallery"', $html);
        $this->assertStringContainsString('data-lightbox-caption="Sunset"', $html);
        $this->assertStringContainsString('aria-label="View larger image: Sunset"', $html);
        $this->assertStringContainsString('<figcaption>Sunset</figcaption>', $html);
    }

    public function testGalleryItemWithoutPictureIsNotZoomable(): void
    {
        $html = ui_partial('gallery_item', ['g' => ['image' => '', 'caption' => 'x'], 'imgUrl' => $this->imgUrl(), 'placeholder' => base_url('assets/img/fleet-placeholder.svg')]);

        $this->assertStringNotContainsString('data-lightbox', $html);
    }

    // ----------------------------------------------------------------- landing

    public function testLandingMakesAboutFleetAndGalleryPicturesZoomable(): void
    {
        $html = $this->landing([
            'aboutImage' => 'uploads/about/a.png',
            'fleet'      => [
                ['id' => 1, 'name' => 'A', 'description' => '', 'image' => 'uploads/fleet/a.jpg', 'whatsapp' => null],
                ['id' => 2, 'name' => 'B', 'description' => '', 'image' => 'assets/img/fleet-placeholder.svg', 'whatsapp' => null],
            ],
            'gallery'    => [['image' => 'uploads/gallery/1.png', 'caption' => 'One'], ['image' => 'uploads/gallery/2.png', 'caption' => 'Two']],
        ]);

        $this->assertSame(1 + 1 + 2, substr_count($html, 'data-lightbox-src='), 'about + 1 fleet + 2 gallery');
        $this->assertSame(1, substr_count($html, 'data-lightbox-group="fleet"'));
        $this->assertSame(2, substr_count($html, 'data-lightbox-group="gallery"'));
    }

    public function testHeroCarouselPicturesAreNotZoomable(): void
    {
        $html  = $this->landing(['heroSlides' => [['image' => 'http://x.test/1.jpg', 'alt' => 'one'], ['image' => 'http://x.test/2.jpg', 'alt' => 'two']]]);
        $start = strpos($html, 'id="hero-carousel"');
        $end   = strpos($html, '</section>', $start);

        $this->assertStringNotContainsString('data-lightbox', substr($html, $start, $end - $start));
    }

    public function testPopupIsRenderedOnceWithLocalizedLabels(): void
    {
        $en = $this->d($this->landing());
        $this->assertSame(1, substr_count($en, 'id="lightbox"'));
        $this->assertStringContainsString('aria-label="Image preview"', $en);
        $this->assertStringContainsString('aria-label="Close"', $en);
        $this->assertStringContainsString('aria-label="Previous image"', $en);
        $this->assertStringContainsString('aria-label="Next image"', $en);
        $this->assertStringContainsString('class="lightbox__img"', $en);
        $this->assertMatchesRegularExpression('#data-lightbox-prev[^>]*hidden#', $en, 'arrows stay hidden until a group is opened');

        $id = $this->d($this->landing(['lang' => 'id']));
        $this->assertStringContainsString('aria-label="Pratinjau gambar"', $id);
        $this->assertStringContainsString('aria-label="Gambar berikutnya"', $id);

        $ms = $this->d($this->landing(['lang' => 'ms']));
        $this->assertStringContainsString('aria-label="Pratonton imej"', $ms);
        $this->assertStringContainsString('aria-label="Imej seterusnya"', $ms);
    }

    public function testZoomLabelFollowsTheLanguage(): void
    {
        $item = ['id' => 1, 'name' => 'Avanza', 'description' => '', 'image' => 'uploads/fleet/a.jpg', 'whatsapp' => null];

        $this->assertStringContainsString('aria-label="Perbesar gambar: Avanza"', $this->d($this->landing(['lang' => 'id', 'fleet' => [$item]])));
        $this->assertStringContainsString('aria-label="Besarkan imej: Avanza"', $this->d($this->landing(['lang' => 'ms', 'fleet' => [$item]])));
    }

    public function testStillOneH1AndNoLeftoverLightboxWhenNothingToZoom(): void
    {
        $html = $this->landing();

        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
        $this->assertSame(0, substr_count($html, 'data-lightbox-src=') - substr_count($html, 'data-lightbox-src=\"\"'), 'nothing to zoom');
        $this->assertSame(1, substr_count($html, 'id="lightbox"'), 'the popup shell is always present');
    }

    // ----------------------------------------------------------------- assets

    public function testStylesheetAndScriptContainTheLightbox(): void
    {
        $css = (string) file_get_contents(FCPATH . 'assets/css/landing.css');
        foreach (['.zoomable:hover img', 'transform: scale(', '.lightbox__close', '.lightbox__img', 'prefers-reduced-motion'] as $needle) {
            $this->assertStringContainsString($needle, $css, $needle);
        }

        $js = (string) file_get_contents(FCPATH . 'assets/js/landing.js');
        foreach (['initLightbox', 'data-lightbox', 'hidden.bs.modal', 'ArrowLeft', 'ArrowRight', 'getOrCreateInstance'] as $needle) {
            $this->assertStringContainsString($needle, $js, $needle);
        }
    }
}