<?php

use App\Controllers\ApiV1\SiteConfigController;
use App\Controllers\LandingController;
use App\Controllers\PublicBaseController;
use App\Libraries\ContentValidator;
use App\Libraries\ProxyResponseMapper;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * About section: optional picture + heading (landing view, validation, saving).
 *
 * @internal
 */
final class AboutSectionTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'ui']);
    }

    /** @param array<string,mixed> $o */
    private function render(array $o = []): string
    {
        $base = [
            'lang' => 'en', 'languages' => ['en', 'id', 'ms'], 'hero' => ['title' => 'Hero', 'subtitle' => 'Sub'],
            'about' => 'First paragraph.', 'galleryTitle' => '', 'brand' => 'Kimigo Travel',
            'contact' => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => null],
            'fleet' => [], 'news' => [], 'faq' => [], 'gallery' => [],
        ];

        return view('landing/index', array_replace($base, $o), ['saveData' => false]);
    }

    private function aboutHtml(string $html): string
    {
        $s = strpos($html, '<section id="about"');
        $e = strpos($html, '</section>', $s);

        return substr($html, $s, $e - $s);
    }

    // ------------------------------------------------------------------- view

    public function testPictureIsShownBesideTheText(): void
    {
        $about = $this->aboutHtml($this->render(['aboutImage' => 'uploads/about/a.png']));

        $this->assertStringContainsString('class="about-media"', $about);
        $this->assertStringNotContainsString('about-grid--single', $about);
        $this->assertMatchesRegularExpression('#<img src="http[^"]*uploads(&\#x2F;|/)about(&\#x2F;|/)a\.png"#', $about);
        $this->assertStringContainsString('loading="lazy"', $about);
    }

    public function testWithoutAPictureTheTextStandsAlone(): void
    {
        $about = $this->aboutHtml($this->render());

        $this->assertStringNotContainsString('about-media', $about);
        $this->assertStringContainsString('about-grid--single', $about);
        $this->assertStringContainsString('First paragraph.', $about);
    }

    public function testAbsoluteImageUrlIsKept(): void
    {
        $about = html_entity_decode($this->aboutHtml($this->render(['aboutImage' => 'https://cdn.example.com/x.jpg'])), ENT_QUOTES | ENT_HTML5);

        $this->assertStringContainsString('src="https://cdn.example.com/x.jpg"', $about);
    }

    public function testHeadingUsesTheCustomTitleOtherwiseTheSiteName(): void
    {
        $custom = $this->aboutHtml($this->render(['aboutTitle' => 'Our Travel Story']));
        $this->assertStringContainsString('<h2 id="about-title" class="about-title">Our Travel <mark class="hl">Story</mark></h2>', $custom);

        $fallback = $this->aboutHtml($this->render());
        $this->assertStringContainsString('<h2 id="about-title" class="about-title">Kimigo <mark class="hl">Travel</mark></h2>', $fallback);
    }

    public function testEyebrowIsLocalized(): void
    {
        $this->assertStringContainsString('<p class="about-eyebrow">About Us</p>', $this->render());
        $this->assertStringContainsString('<p class="about-eyebrow">Tentang Kami</p>', $this->render(['lang' => 'id']));
        $this->assertStringContainsString('<p class="about-eyebrow">Tentang Kami</p>', $this->render(['lang' => 'ms']));
    }

    public function testBlankLinesBecomeSeparateParagraphs(): void
    {
        $about = $this->aboutHtml($this->render(['about' => "One.\n\nTwo.\r\n\r\n\r\nThree.\nstill three."]));

        $this->assertSame(3, substr_count($about, 'class="about-text"'));
        $this->assertStringContainsString('Two.', $about);
    }

    public function testEmptyAboutShowsTheEmptyState(): void
    {
        $about = $this->aboutHtml($this->render(['about' => '  ']));

        $this->assertStringContainsString('About information is currently unavailable.', $about);
        $this->assertStringNotContainsString('about-text', $about);
    }

    public function testHostileHeadingAndPathAreEscaped(): void
    {
        $about = $this->aboutHtml($this->render([
            'aboutTitle' => '<script>alert(1)</script> Story',
            'aboutImage' => 'x.png" onerror="alert(1)',
        ]));

        $this->assertStringNotContainsString('<script>', $about);
        $this->assertStringNotContainsString('" onerror="', $about);
    }

    public function testStillExactlyOneH1AndOneAboutHeading(): void
    {
        $html = $this->render(['aboutImage' => 'uploads/about/a.png', 'aboutTitle' => 'Story']);

        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
        $this->assertSame(1, substr_count($html, 'id="about-title"'));
    }

    public function testWideAndSquarePicturesGetTheirOwnLayout(): void
    {
        $wide = $this->aboutHtml($this->render(['aboutImage' => 'uploads/about/a.png', 'aboutImageWide' => true]));
        $this->assertStringContainsString('about-grid about-grid--wide', $wide);

        $square = $this->aboutHtml($this->render(['aboutImage' => 'uploads/about/a.png', 'aboutImageWide' => false]));
        $this->assertStringNotContainsString('about-grid--wide', $square);

        // The wide layout only makes sense with a picture.
        $none = $this->aboutHtml($this->render(['aboutImageWide' => true]));
        $this->assertStringNotContainsString('about-grid--wide', $none);
        $this->assertStringContainsString('about-grid--single', $none);
    }

    /** @return array<string, array{0:int,1:int,2:bool}> */
    public static function ratioProvider(): array
    {
        return [
            'banner 16:9'   => [1672, 941, true],
            'exactly 1.6'   => [1600, 1000, true],
            'photo 3:2'     => [1536, 1024, false],
            'photo 1.4'     => [1400, 1000, false],
            'photo 4:3'     => [1200, 900, false],
            'square'        => [800, 800, false],
            'portrait'      => [600, 900, false],
        ];
    }

    /** @dataProvider ratioProvider */
    public function testOnlyWideBannersAreStackedAboveTheText(int $w, int $h, bool $expected): void
    {
        $dir = WRITEPATH . 'cache';
        $file = $dir . DIRECTORY_SEPARATOR . 'about-ratio-' . $w . 'x' . $h . '.png';
        $im = imagecreatetruecolor($w, $h);
        imagepng($im, $file);
        imagedestroy($im);

        try {
            $this->assertSame($expected, $this->isWide('../writable/cache/' . basename($file)));
        } finally {
            @unlink($file);
        }
    }

    public function testMissingRemoteAndEmptyPathsAreNeverWide(): void
    {
        foreach (['', 'uploads/about/does-not-exist.png', 'https://cdn.example.com/banner.jpg'] as $path) {
            $this->assertFalse($this->isWide($path), $path);
        }
    }

    private function isWide(string $path): bool
    {
        $ref    = new ReflectionClass(LandingController::class);
        $method = $ref->getMethod('isWideImage');
        $method->setAccessible(true);

        return $method->invoke($ref->newInstanceWithoutConstructor(), $path);
    }
    // -------------------------------------------------------------- validation

    public function testAboutTitleLengthLimitPerLanguage(): void
    {
        $this->assertSame([], ContentValidator::validateAboutSettings(['about_title_en' => str_repeat('a', 150)]));

        $errors = ContentValidator::validateAboutSettings(['about_title_en' => str_repeat('a', 151), 'about_title_ms' => str_repeat('b', 151), 'about_title_id' => 'ok']);
        $this->assertSame(['about_title_en', 'about_title_ms'], array_keys($errors));
    }

    public function testAboutExtrasAreOptional(): void
    {
        $this->assertSame([], ContentValidator::validateAboutSettings([]));
        $this->assertSame([], ContentValidator::validateAboutSettings(['about_title_en' => '', 'about_image' => null]));
        $this->assertSame(['about_image'], array_keys(ContentValidator::validateAboutSettings(['about_image' => str_repeat('x', 501)])));
    }

    private function validConfig(array $o = []): array
    {
        $cfg = [];
        foreach (['hero_title', 'hero_subtitle', 'about_body', 'gallery_title', 'contact_body'] as $base) {
            foreach (['en', 'id', 'ms'] as $lang) {
                $cfg["{$base}_{$lang}"] = "{$base} {$lang}";
            }
        }

        return array_merge($cfg, $o);
    }

    public function testSavingTheAboutExtras(): void
    {
        $r = SiteConfigController::prepareSave(null, $this->validConfig(['about_title_en' => ' Our Story ', 'about_title_id' => 'Kisah Kami']));

        $this->assertSame([], $r['errors']);
        $this->assertSame('Our Story', $r['record']['about_title_en']);
        $this->assertSame('Kisah Kami', $r['record']['about_title_id']);
    }

    public function testEmptyAboutExtrasAreStoredAsNull(): void
    {
        $r = SiteConfigController::prepareSave(null, $this->validConfig(['about_title_en' => '', 'about_title_id' => '', 'about_image' => '']));

        $this->assertSame([], $r['errors']);
        $this->assertNull($r['record']['about_title_en']);
        $this->assertNull($r['record']['about_title_id']);
        $this->assertNull($r['record']['about_image']);
    }

    public function testTooLongAboutTitleRejectsTheWholeSave(): void
    {
        $r = SiteConfigController::prepareSave(null, $this->validConfig(['about_title_en' => str_repeat('a', 151)]));

        $this->assertArrayHasKey('about_title_en', $r['errors']);
        $this->assertSame([], $r['record'], 'nothing is persisted on a rejected save');
    }

    public function testAboutImageIsAnAllowedConfigField(): void
    {
        $this->assertContains('about_image', SiteConfigController::FIELDS);
        $this->assertSame('uploads/about', SiteConfigController::IMAGE_DIRS['about_image']);
        $this->assertSame('uploads/seo', SiteConfigController::IMAGE_DIRS['og_image']);
    }

    // ---------------------------------------------------------- upload errors

    public function testUploadFailureNamesTheRightField(): void
    {
        $r = ProxyResponseMapper::uploadFailure(UPLOAD_ERR_INI_SIZE, 'about_image');

        $this->assertSame(422, $r['status']);
        $this->assertSame(['about_image'], array_keys($r['errors']));
        $this->assertSame(['image'], array_keys(ProxyResponseMapper::uploadFailure(UPLOAD_ERR_PARTIAL)['errors']), 'default field is unchanged');
    }

    // ------------------------------------------------------ landing controller

    public function testLandingControllerPassesTheAboutPictureAndLocalizedHeading(): void
    {
        $stub = static fn (string $m, array $r) => new class ($m, $r) {
            public function __construct(private string $m, private array $r)
            {
            }

            public function __call($n, $a)
            {
                return ['status' => 200, 'message' => '', 'data' => $this->r, 'total' => count($this->r)];
            }
        };
        $config = ['about_body_en' => 'Body', 'about_title_en' => 'Our Story', 'about_title_id' => 'Kisah Kami', 'about_image' => 'uploads/about/a.png', 'site_name_en' => 'Kimigo Travel'];

        $ref = new ReflectionClass(LandingController::class);
        $make = function (string $lang) use ($ref, $stub, $config): string {
            $c = $ref->newInstanceWithoutConstructor();
            foreach (['apiSiteConfig' => $config, 'apiFleet' => [], 'apiGallery' => [], 'apiNews' => [], 'apiFaq' => []] as $prop => $data) {
                $p = $ref->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($c, $stub('x', $data));
            }
            $l = new ReflectionProperty(PublicBaseController::class, 'lang');
            $l->setAccessible(true);
            $l->setValue($c, $lang);

            return $c->index();
        };

        $en = $make('en');
        $this->assertStringContainsString('class="about-media"', $en);
        $this->assertStringContainsString('Our <mark class="hl">Story</mark>', $en);

        $id = $make('id');
        $this->assertStringContainsString('Kisah <mark class="hl">Kami</mark>', $id);
    }
}