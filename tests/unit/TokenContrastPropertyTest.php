<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Computes WCAG contrast ratios from the hex values in tokens.css and checks
 * every pair of the contrast matrix declared in design.md.
 *
 * @internal
 */
final class TokenContrastPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Contrast matrix: [background, foreground, threshold].
     * A value starting with '#' is a literal color, otherwise a token name.
     *
     * @return list<array{0:string,1:string,2:float}>
     */
    private static function matrix(): array
    {
        return [
            ['--c-surface', '--c-text', 4.5],
            ['--c-surface', '--c-muted', 4.5],
            ['--c-surface-2', '--c-muted', 4.5],
            ['--lp-lime', '--lp-lime-ink', 4.5],
            ['--c-ink', '#ffffff', 4.5],
            ['--ad-primary', '#ffffff', 4.5],
            ['--c-surface', '--ad-primary', 4.5],
            ['--ad-primary-soft', '--ad-primary', 4.5],
            ['--c-surface', '--ad-green-text', 4.5],
            ['--c-surface', '--focus-ring', 3.0],
            ['--c-ink', '--focus-ring-on-dark', 3.0],
        ];
    }

    /**
     * @return array<string,string> token name => normalized "#rrggbb"
     */
    private function hexTokens(): array
    {
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/tokens.css');
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);

        preg_match_all('/(--[a-z0-9-]+)\s*:\s*(#[0-9a-fA-F]{3}|#[0-9a-fA-F]{6})\s*;/', $css, $m, PREG_SET_ORDER);

        $tokens = [];
        foreach ($m as $row) {
            $tokens[$row[1]] = self::normalizeHex($row[2]);
        }

        return $tokens;
    }

    private static function normalizeHex(string $hex): string
    {
        $hex = strtolower(ltrim($hex, '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return '#' . $hex;
    }

    private static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [];
        foreach ([0, 2, 4] as $i) {
            $c = hexdec(substr($hex, $i, 2)) / 255;
            $channels[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private static function ratio(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * @param array<string,string> $tokens
     */
    private static function resolve(array $tokens, string $ref): string
    {
        if ($ref[0] === '#') {
            return self::normalizeHex($ref);
        }
        self::assertArrayHasKey($ref, $tokens, 'Token ' . $ref . ' must be defined as hex in tokens.css');

        return $tokens[$ref];
    }

    public function testRatioHelperMatchesKnownValues(): void
    {
        self::assertEqualsWithDelta(21.0, self::ratio('#000000', '#ffffff'), 0.001);
        self::assertEqualsWithDelta(1.0, self::ratio('#777777', '#777777'), 0.001);
    }

    /**
     * Validates: Requirements 1.1, 1.3, 11.1, 11.2, 14.3, 14.4
     */
    public function testAllMatrixPairsMeetContrastThreshold(): void
    {
        // Feature: ui-redesign-reference, Property 6: Pasangan warna token memenuhi kontras
        $tokens = $this->hexTokens();
        $matrix = self::matrix();

        $this->limitTo(100)->forAll(
            Generators::elements(array_keys($matrix))
        )->then(function (int $idx) use ($tokens, $matrix): void {
            [$bg, $fg, $threshold] = $matrix[$idx];
            $ratio = self::ratio(self::resolve($tokens, $bg), self::resolve($tokens, $fg));

            self::assertGreaterThanOrEqual(
                $threshold,
                $ratio,
                sprintf('%s on %s has contrast %.2f, expected >= %.1f', $fg, $bg, $ratio, $threshold)
            );
        });
    }

    /**
     * Lime and green are background-only colors: they must not pass as text on white.
     *
     * Validates: Requirements 11.3
     */
    public function testLimeAndGreenAreNotUsableAsTextOnWhite(): void
    {
        $tokens = $this->hexTokens();
        $white  = $tokens['--c-surface'];

        self::assertLessThan(4.5, self::ratio($white, $tokens['--lp-lime']));
        self::assertLessThan(4.5, self::ratio($white, $tokens['--ad-green']));
    }
}
