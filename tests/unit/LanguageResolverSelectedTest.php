<?php

use App\Libraries\LanguageResolver;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LanguageResolverSelectedTest extends TestCase
{
    use TestTrait;

    /**
     * Validates: Requirements 1.2, 2.3
     */
    public function testSelectedLanguageValueIsReturned(): void
    {
        // Feature: travel-rental-website, Property 1: Selected-language resolution
        $this->limitTo(100)->forAll(
            Generators::elements(LanguageResolver::SUPPORTED),
            Generators::string(),
            Generators::string(),
            Generators::string()
        )->then(static function (string $lang, string $suffix, string $other1, string $other2): void {
            $selected = 'x' . $suffix; // guaranteed non-empty, avoids filter discards
            $values   = [];
            $others = [$other1, $other2];
            foreach (LanguageResolver::SUPPORTED as $code) {
                $values[$code] = $code === $lang ? $selected : array_shift($others);
            }

            self::assertSame($selected, LanguageResolver::resolve($values, $lang));
        });
    }
}
