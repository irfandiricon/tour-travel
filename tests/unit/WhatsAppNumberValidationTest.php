<?php

use App\Libraries\WhatsAppLinkBuilder;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WhatsAppNumberValidationTest extends TestCase
{
    use TestTrait;

    /**
     * Candidate generator mixing valid E.164 values, near-misses and arbitrary strings.
     */
    private function candidates(): mixed
    {
        return Generators::oneOf(
            // valid: + followed by 8-15 digits
            Generators::map(
                static fn (array $d): string => '+' . implode('', $d),
                Generators::bind(
                    Generators::choose(8, 15),
                    static fn (int $n) => Generators::vector($n, Generators::choose(0, 9))
                )
            ),
            // near-misses: wrong digit count, missing plus, trailing newline, spaces, letters
            Generators::map(
                static fn (array $p): string => $p[0] . $p[1],
                Generators::tuple(
                    Generators::elements('', '+', '++', ' +', '00', '+ '),
                    Generators::map(
                        static fn (array $x): string => str_repeat('5', $x[0]) . $x[1],
                        Generators::tuple(
                            Generators::choose(0, 20),
                            Generators::elements('', "\n", ' ', 'a', '-1', '.5')
                        )
                    )
                )
            ),
            Generators::string()
        );
    }

    /**
     * Validates: Requirements 7.1, 7.2, 7.3
     */
    public function testValidationMatchesE164AndRetainsPreviousOnFailure(): void
    {
        // Feature: travel-rental-website, Property 15: WhatsApp number E.164 validation with retain-previous
        $this->limitTo(100)->forAll(
            $this->candidates(),
            Generators::elements('+6281234567890', '+14155550123', '')
        )->then(static function (string $candidate, string $previous): void {
            $expected = preg_match('/^\+\d{8,15}$/D', $candidate) === 1;
            self::assertSame($expected, WhatsAppLinkBuilder::isValidNumber($candidate));

            // Tiny in-memory store: persists only when validation passes.
            $store = new class ($previous) {
                public function __construct(public string $value)
                {
                }

                public function save(string $number): bool
                {
                    if (! WhatsAppLinkBuilder::isValidNumber($number)) {
                        return false;
                    }
                    $this->value = $number;

                    return true;
                }
            };

            $saved = $store->save($candidate);

            if ($expected) {
                self::assertTrue($saved);
                self::assertSame($candidate, $store->value);
            } else {
                self::assertFalse($saved);
                self::assertSame($previous, $store->value);
            }
        });
    }
}
