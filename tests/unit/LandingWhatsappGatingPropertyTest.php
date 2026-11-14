<?php

use CodeIgniter\Test\CIUnitTestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * @internal
 */
final class LandingWhatsappGatingPropertyTest extends CIUnitTestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
    }

    /** @param array<string, mixed> $data */
    private function render(array $data): string
    {
        $base = [
            'lang'         => 'en',
            'languages'    => ['en', 'id', 'ms'],
            'hero'         => ['title' => 'Hero title', 'subtitle' => 'Sub'],
            'about'        => 'About text',
            'galleryTitle' => '',
            'contact'      => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => null],
            'fleet'        => [],
            'news'         => [],
            'faq'          => [],
            'gallery'      => [],
        ];

        return view('landing/index', array_replace($base, $data), ['saveData' => false]);
    }

    /**
     * Validates: Requirements 2.6, 2.7, 3.10, 5.6, 5.7
     */
    public function testWhatsappElementsAppearOnlyWhenLinkExists(): void
    {
        // Feature: ui-redesign-reference, Property 8: Elemen WhatsApp hanya muncul bila tautan ada
        $this->limitTo(100)->forAll(
            Generators::elements(['en', 'id', 'ms']),
            Generators::bool(),
            Generators::vector(6, Generators::bool()),
            Generators::choose(0, 6)
        )->then(function (string $lang, bool $contactHasWa, array $fleetFlags, int $fleetCount): void {
            $flags = array_slice($fleetFlags, 0, $fleetCount);

            $fleet = [];
            foreach ($flags as $i => $hasWa) {
                $fleet[] = [
                    'id'          => $i + 1,
                    'name'        => 'Car ' . ($i + 1),
                    'description' => 'd',
                    'image'       => 'x.jpg',
                    'whatsapp'    => $hasWa ? 'https://wa.me/62812000' . $i . '?text=Hi' : null,
                ];
            }

            $contactLink = $contactHasWa ? 'https://wa.me/628123456789?text=Hello' : null;

            $html = $this->render([
                'lang'    => $lang,
                'fleet'   => $fleet,
                'contact' => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => $contactLink],
            ]);

            $expectedBookings = count(array_filter($flags));
            $contactCount     = $contactHasWa ? 1 : 0;

            // Per-fleet booking button and "ready to book" chip.
            self::assertSame($expectedBookings, substr_count($html, 'data-track-booking='));
            self::assertSame($expectedBookings, substr_count($html, 'data-fleet-id='));
            self::assertSame($expectedBookings, substr_count($html, 'data-fleet-name='));
            self::assertSame($expectedBookings, substr_count($html, 'class="chip-lime"'));

            // Hero button, contact button, and floating button follow the contact link.
            self::assertSame($contactCount, substr_count($html, 'btn btn-wa btn-success btn-lg'));
            self::assertSame($contactCount, substr_count($html, 'btn-lime btn-wa'));
            self::assertSame($contactCount, substr_count($html, 'class="wa-float"'));

            if (! $contactHasWa && $expectedBookings === 0) {
                self::assertStringNotContainsString('wa.me', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
            }
        });
    }
}
