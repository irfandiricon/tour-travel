<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UiHighlightPropertyTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        helper('ui');
    }

    /**
     * Potongan teks: kata unicode, spasi, dan karakter markup.
     */
    private function texts(): \Eris\Generator
    {
        $fragments = Generators::elements(
            'Jelajahi', 'Bali', 'wisata', 'été', 'Ñandú', '日本語', 'مرحبا', '🚐', 'a',
            ' ', '  ', "\t", "\n",
            '<script>alert(1)</script>', '<b>', '</mark>', '<mark class="hl">',
            '"', "'", '&', '&amp;', '<', '>', '&lt;'
        );

        return Generators::map(
            static fn (array $parts): string => implode('', $parts),
            Generators::seq($fragments)
        );
    }

    private function normalize(string $s): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $s));
    }

    /**
     * Validates: Requirements 3.2, 13.1, 13.2, 13.3, 13.4
     */
    public function testHighlightIsSafeAndPreservesText(): void
    {
        // Feature: ui-redesign-reference, Property 2: Sorotan kata terakhir aman dan menjaga teks
        $this->limitTo(100)->forAll($this->texts())->then(function (string $s): void {
            $out = ui_highlight_last_word($s);

            if (trim($s) === '') {
                $this->assertSame('', $out);

                return;
            }

            // Hanya tag <mark class="hl"> dan </mark> yang boleh muncul.
            $withoutMark = str_replace(['<mark class="hl">', '</mark>'], '', $out);
            $this->assertStringNotContainsString('<', $withoutMark);
            $this->assertStringNotContainsString('>', $withoutMark);
            $this->assertSame(
                substr_count($out, '<mark class="hl">'),
                substr_count($out, '</mark>')
            );
            $this->assertLessThanOrEqual(1, substr_count($out, '<mark class="hl">'));

            // Teks setelah strip_tags sama dengan esc(trim(s)) (spasi dinormalkan).
            $this->assertSame(
                $this->normalize(esc(trim($s))),
                $this->normalize(strip_tags($out))
            );
        });
    }

    /**
     * Validates: Requirements 13.1, 13.2
     */
    public function testWhitespaceOnlyInputYieldsEmptyString(): void
    {
        // Feature: ui-redesign-reference, Property 2: Sorotan kata terakhir aman dan menjaga teks
        $this->limitTo(100)->forAll(
            Generators::map(
                static fn (array $p): string => implode('', $p),
                Generators::seq(Generators::elements(' ', "\t", "\n", "\r"))
            )
        )->then(function (string $s): void {
            $this->assertSame('', ui_highlight_last_word($s));
        });
    }
}
