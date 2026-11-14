<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UiInitialsPropertyTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        helper('ui');
    }

    /**
     * Names built from unicode letters, markup characters, digits, and whitespace.
     */
    private function names(): mixed
    {
        $pieces = [
            'a', 'Budi', 'siti', 'Émile', 'ñandú', 'Ωmega', 'Иван', '李', 'ß',
            '<', '>', '&', '"', "'", '<script>', '</b>', '1', '23', '-', '_', '.',
            ' ', '  ', "\t", "\n", '',
        ];

        return Generators::map(
            static fn (array $parts): string => implode('', $parts),
            Generators::seq(Generators::elements($pieces))
        );
    }

    /**
     * Validates: Requirements 7.4, 7.8, 13.5, 13.6
     */
    public function testInitialsAreShortUppercaseAndSafe(): void
    {
        // Feature: ui-redesign-reference, Property 3: Inisial avatar selalu pendek dan aman
        $this->limitTo(100)->forAll(
            Generators::oneOf($this->names(), Generators::string())
        )->then(static function (string $name): void {
            $result = ui_initials($name);

            // 1 or 2 characters.
            $length = mb_strlen($result, 'UTF-8');
            self::assertGreaterThanOrEqual(1, $length);
            self::assertLessThanOrEqual(2, $length);

            // No markup-sensitive characters.
            self::assertDoesNotMatchRegularExpression('/[<>&"\']/u', $result);

            if (preg_match('/\p{L}/u', $name) === 1) {
                // Uppercase letters only.
                self::assertSame(mb_strtoupper($result, 'UTF-8'), $result);
                self::assertMatchesRegularExpression('/^\p{L}{1,2}$/u', $result);
            } else {
                // No usable letter: fallback.
                self::assertSame('A', $result);
            }
        });
    }
}
