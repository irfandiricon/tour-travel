<?php

use App\Libraries\WhatsAppLinkBuilder;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WhatsAppLinkTest extends TestCase
{
    use TestTrait;

    private const POOL = ['a', 'Z', '7', ' ', '&', '=', '?', '#', '%', '+', 'é', 'ü', '日', '本', '😀', "\n"];

    private function prefillGenerator()
    {
        return Generators::map(
            static fn (array $chars): string => implode('', $chars),
            Generators::seq(Generators::elements(...self::POOL))
        );
    }

    private function longPrefillGenerator()
    {
        return Generators::map(
            static fn (array $chars): string => str_repeat(implode('', $chars) . 'x', 60),
            Generators::seq(Generators::elements(...self::POOL))
        );
    }

    /**
     * Validates: Requirements 7.5, 7.6, 7.7
     */
    public function testValidNumberYieldsWaMeLinkWithTruncatedPrefill(): void
    {
        // Feature: travel-rental-website, Property 16: WhatsApp link generation and booking visibility
        $this->limitTo(100)->forAll(
            Generators::choose(8, 15),
            Generators::choose(1, 9),
            Generators::choose(0, 9),
            Generators::oneOf($this->prefillGenerator(), $this->longPrefillGenerator())
        )->then(function (int $len, int $first, int $seed, string $prefill): void {
            $digits = (string) $first;
            for ($i = 1; $i < $len; $i++) {
                $digits .= (string) (($seed + $i * 7) % 10);
            }
            $link = WhatsAppLinkBuilder::build('+' . $digits, $prefill);

            $this->assertNotNull($link);
            $parts = explode('?', $link, 2);
            $this->assertSame('https://wa.me/' . $digits, $parts[0]);

            $expected = mb_substr($prefill, 0, 200);
            if ($expected === '') {
                $this->assertCount(1, $parts);

                return;
            }
            $this->assertCount(2, $parts);
            parse_str($parts[1], $query);
            $this->assertSame($expected, $query['text']);
            $this->assertLessThanOrEqual(200, mb_strlen($query['text']));
        });
    }

    /**
     * Validates: Requirements 7.7
     */
    public function testInvalidOrEmptyNumberYieldsNull(): void
    {
        // Feature: travel-rental-website, Property 16: WhatsApp link generation and booking visibility
        $this->limitTo(100)->forAll(
            Generators::oneOf(
                Generators::constant(null),
                Generators::constant(''),
                Generators::constant('   '),
                Generators::constant("+123456789\n"),
                Generators::map(static fn (string $s): string => '+' . $s, Generators::string()),
                Generators::map(static fn (int $n): string => (string) $n, Generators::choose(10000000, 999999999)),
                Generators::map(static fn (int $n): string => '+' . $n, Generators::choose(0, 9999999)),
                Generators::map(static fn (int $n): string => '+' . $n . '0000000000', Generators::choose(10000000, 99999999))
            ),
            $this->prefillGenerator()
        )->then(function (?string $number, string $prefill): void {
            // Skip the rare random string that happens to be a valid E.164 number.
            if (WhatsAppLinkBuilder::isValidNumber($number)) {
                $this->assertNotNull(WhatsAppLinkBuilder::build($number, $prefill));

                return;
            }
            $this->assertNull(WhatsAppLinkBuilder::build($number, $prefill));
        });
    }
}
