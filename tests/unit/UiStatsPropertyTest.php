<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UiStatsPropertyTest extends TestCase
{
    use TestTrait;

    private const PRIORITY = ['fleet', 'gallery', 'news', 'faq'];

    protected function setUp(): void
    {
        parent::setUp();
        helper('ui');
    }

    /**
     * Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6
     */
    public function testStatsAreValidOrderedAndCapped(): void
    {
        // Feature: ui-redesign-reference, Property 1: Statistik landing selalu valid dan terurut
        $this->limitTo(100)->forAll(
            Generators::vector(4, Generators::choose(-5, 50)), // counts per priority key (incl. zero/negative)
            Generators::vector(4, Generators::bool()),         // whether a label exists for the key
            Generators::vector(4, Generators::string())        // label text
        )->then(static function (array $countList, array $hasLabel, array $labelText): void {
            $counts = [];
            $labels = [];
            foreach (self::PRIORITY as $i => $key) {
                $counts[$key] = $countList[$i];
                if ($hasLabel[$i]) {
                    $labels[$key] = $labelText[$i];
                }
            }

            $result = ui_landing_stats($counts, $labels);

            // Expected eligible keys in priority order, capped at 3.
            $expectedKeys = [];
            foreach (self::PRIORITY as $key) {
                if ($counts[$key] > 0 && isset($labels[$key])) {
                    $expectedKeys[] = $key;
                }
            }
            $expectedKeys = array_slice($expectedKeys, 0, 3);

            // At most 3 cards; empty when nothing eligible.
            self::assertLessThanOrEqual(3, count($result));
            self::assertSame($expectedKeys, array_column($result, 'key'));
            if ($expectedKeys === []) {
                self::assertSame([], $result);
            }

            foreach ($result as $index => $card) {
                self::assertGreaterThan(0, $card['value']);
                self::assertSame($counts[$card['key']], $card['value']);
                self::assertSame($labels[$card['key']], $card['label']);
                // Only the first card is dark.
                self::assertSame($index === 0, $card['dark']);
            }
        });
    }
}
