<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'Helpers/ui_helper.php';

/**
 * @internal
 */
final class UiNavActivePropertyTest extends TestCase
{
    use TestTrait;

    /** @var list<string> */
    private const NAV = [
        'dashboard',
        'admin/information',
        'admin/news',
        'admin/fleet',
        'admin/faq',
        'admin/gallery',
    ];

    /**
     * Request-path generator: random strings, nav paths with random
     * suffixes (segment and non-segment), and optional surrounding slashes.
     */
    private function paths(): mixed
    {
        $suffix = Generators::elements('', '/', '/create', '/edit/5', 'letter', 'letter/x', 's', '2', '-x');

        return Generators::oneOf(
            Generators::string(),
            Generators::map(
                static fn (array $t): string => $t[0] . $t[1] . $t[2],
                Generators::tuple(
                    Generators::elements(...self::NAV),
                    $suffix,
                    Generators::elements('', '/')
                )
            ),
            Generators::map(
                static fn (string $s): string => 'admin/' . $s,
                Generators::string()
            )
        );
    }

    /**
     * Validates: Requirements 7.7, 13.7, 13.8
     */
    public function testAtMostOneActiveItemAndSegmentRules(): void
    {
        // Feature: ui-redesign-reference, Property 4: Item navigasi aktif konsisten
        $this->limitTo(100)->forAll($this->paths())->then(static function (string $path): void {
            $r      = trim($path, '/');
            $active = [];

            foreach (self::NAV as $item) {
                if (ui_nav_is_active($path, $item)) {
                    $active[] = $item;
                }
            }

            self::assertLessThanOrEqual(1, count($active));

            foreach (self::NAV as $item) {
                $expected = $item === 'dashboard'
                    ? $r === 'dashboard'
                    : ($r === $item || str_starts_with($r, $item . '/'));

                self::assertSame($expected, ui_nav_is_active($path, $item));
            }

            // Prefix tanpa pemisah segmen tidak boleh aktif.
            if ($r === 'admin/newsletter' || str_starts_with($r, 'admin/newsletter/')) {
                self::assertFalse(ui_nav_is_active($path, 'admin/news'));
            }
            if ($r !== 'dashboard') {
                self::assertFalse(ui_nav_is_active($path, 'dashboard'));
            }
        });
    }

    /**
     * Validates: Requirements 7.7, 13.8
     */
    public function testNewsletterIsNotNewsAndSubpathsAreActive(): void
    {
        // Feature: ui-redesign-reference, Property 4: Item navigasi aktif konsisten
        $this->limitTo(100)->forAll(
            Generators::elements(...array_slice(self::NAV, 1)),
            Generators::elements('', '/create', '/edit/7', '/')
        )->then(static function (string $item, string $suffix): void {
            self::assertTrue(ui_nav_is_active($item . $suffix, $item));
            self::assertFalse(ui_nav_is_active($item . 'letter', $item));
        });

        self::assertFalse(ui_nav_is_active('admin/newsletter', 'admin/news'));
        self::assertTrue(ui_nav_is_active('dashboard', 'dashboard'));
        self::assertFalse(ui_nav_is_active('dashboard/x', 'dashboard'));
    }
}
