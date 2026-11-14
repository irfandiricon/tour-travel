<?php

use App\Controllers\LandingController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Landing-page + WhatsApp integration tests (DB-free: proxies are stubbed).
 *
 * Validates: Requirements 1.1, 1.7, 7.5, 7.6
 *
 * @internal
 */
final class LandingIntegrationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
    }

    /** Proxy stub returning a fixed array result, or throwing. */
    private function stub(string $method, $result, bool $throw = false): object
    {
        return new class ($method, $result, $throw) {
            public function __construct(private string $m, private $r, private bool $t)
            {
            }

            public function __call($name, $args)
            {
                if ($name !== $this->m) {
                    throw new BadMethodCallException($name);
                }
                if ($this->t) {
                    throw new RuntimeException('upstream down');
                }

                return $this->r;
            }
        };
    }

    private function ok(array $data): array
    {
        return ['status' => 200, 'message' => '', 'data' => $data, 'total' => count($data)];
    }

    /** @param array<string, object> $proxies property => stub */
    private function controller(array $proxies, string $lang = 'en'): LandingController
    {
        $ref        = new ReflectionClass(LandingController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        foreach ($proxies as $prop => $stub) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($controller, $stub);
        }

        $l = new ReflectionProperty(\App\Controllers\PublicBaseController::class, 'lang');
        $l->setAccessible(true);
        $l->setValue($controller, $lang);

        return $controller;
    }

    private function fullProxies(string $number = '+628123456789'): array
    {
        return [
            'apiSiteConfig' => $this->stub('apiGetConfig', $this->ok([
                'whatsapp_number' => $number,
                'hero_title_en'   => 'Explore Bali', 'hero_subtitle_en' => 'Best rides',
                'about_body_en'   => 'We are a rental company.',
                'gallery_title_en' => 'Our Moments',
                'contact_body_en' => 'Reach us anytime.',
                'contact_email'   => 'hi@example.com', 'contact_address' => 'Jl. Sunset 1',
            ])),
            'apiFleet' => $this->stub('apiListActive', $this->ok([
                ['id' => 1, 'name_en' => 'Toyota Avanza', 'description_en' => 'MPV', 'image_path' => 'uploads/a.jpg'],
                ['id' => 2, 'name_en' => 'Honda Brio & Co', 'description_en' => 'City car', 'image_path' => ''],
            ])),
            'apiNews' => $this->stub('apiListActive', $this->ok([
                ['status' => 'published', 'publish_date' => '2024-01-01 00:00:00', 'title_en' => 'News Title', 'body_en' => 'News Body'],
            ])),
            'apiFaq' => $this->stub('apiListActive', $this->ok([
                ['question_en' => 'Do you deliver?', 'answer_en' => 'Yes we do'],
            ])),
            'apiGallery' => $this->stub('apiListActive', $this->ok([
                ['image_path' => 'uploads/g.jpg', 'caption_en' => 'Sunset shot'],
            ])),
        ];
    }

    private function decoded(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
    }

    public function testAllSectionsRenderEndToEndWithinThreeSeconds(): void
    {
        $controller = $this->controller($this->fullProxies());

        $start = microtime(true);
        $html  = $controller->index();
        $took  = microtime(true) - $start;

        $this->assertLessThan(3.0, $took, 'Landing must render within 3 seconds (Req 1.1)');
        $this->assertIsString($html);

        foreach (['top', 'about', 'fleet', 'gallery', 'news', 'faq', 'contact'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html, "Missing section #{$id}");
        }
        foreach (['Explore Bali', 'We are a rental company.', 'Toyota Avanza', 'Sunset shot', 'News Title',
            'Do you deliver?', 'Yes we do', 'hi@example.com', 'Jl. Sunset 1', 'Our Moments'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function testFailedOrEmptyProxyResultsDoNotAbortThePage(): void
    {
        $failures = [
            'apiSiteConfig'  => $this->stub('apiGetConfig', ['status' => 502, 'message' => 'x', 'data' => [], 'total' => 0]),
            'apiFleet'       => $this->stub('apiListActive', null),
            'apiNews'        => $this->stub('apiListActive', ['status' => 200, 'data' => 'garbage']),
            'apiFaq'         => $this->stub('apiListActive', ['status' => 500]),
            'apiGallery'     => $this->stub('apiListActive', $this->ok([])),
        ];

        $html = $this->controller($failures)->index();

        $this->assertIsString($html);
        foreach (['about', 'fleet', 'gallery', 'news', 'faq', 'contact'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html);
        }
        $this->assertStringContainsString('No vehicles are available at the moment.', $html);
        $this->assertStringContainsString('No news or information to show yet.', $html);
        $this->assertStringNotContainsString('wa.me', $this->decoded($html));
    }

    public function testOneFailingSectionDoesNotAffectOthers(): void
    {
        $proxies             = $this->fullProxies();
        $proxies['apiFleet'] = $this->stub('apiListActive', [], true);

        $html = $this->controller($proxies)->index();

        $this->assertStringContainsString('No vehicles are available at the moment.', $html);
        $this->assertStringContainsString('News Title', $html);
        $this->assertStringContainsString('Do you deliver?', $html);
    }

    public function testValidNumberYieldsWorkingWaMeLinksInFleetHeroAndContact(): void
    {
        $html = $this->decoded($this->controller($this->fullProxies('+628123456789'))->index());

        preg_match_all('#href="(https://wa\.me/[^"]*)"#', $html, $m);
        $links = $m[1];

        // 2 fleet cards + hero + contact + floating button
        $this->assertCount(5, $links);

        foreach ($links as $link) {
            $this->assertMatchesRegularExpression('#^https://wa\.me/628123456789(\?|$)#', $link);
        }

        // Fleet cards carry the fleet name in the text param.
        $texts = [];
        foreach ($links as $link) {
            parse_str((string) parse_url($link, PHP_URL_QUERY), $q);
            $texts[] = $q['text'] ?? '';
        }
        // Document order: hero, fleet #1, fleet #2, contact.
        $this->assertStringContainsString('Toyota Avanza', $texts[1]);
        $this->assertStringContainsString('Honda Brio & Co', $texts[2]);

        // Hero link sits before #about, contact link after #contact.
        $this->assertLessThan(strpos($html, 'id="about"'), strpos($html, 'wa.me'));
        $this->assertGreaterThan(strpos($html, 'id="contact"'), strrpos($html, 'wa.me'));
    }

    public function testInvalidOrMissingNumberHidesBookingLinks(): void
    {
        foreach (['', '08123456789', 'abc'] as $bad) {
            $html = $this->decoded($this->controller($this->fullProxies($bad))->index());
            $this->assertStringNotContainsString('wa.me', $html, "number '{$bad}'");
            $this->assertStringNotContainsString('Book via WhatsApp', $html);
        }
    }
}
