<?php

use App\Libraries\LanguageResolver;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LanguagePersistenceTest extends TestCase
{
    use TestTrait;

    /**
     * Validates: Requirements 2.5
     */
    public function testInvalidLanguagePreservesPersistedSelection(): void
    {
        // Feature: travel-rental-website, Property 3: Invalid language preserves persisted selection
        $this->limitTo(100)->forAll(
            Generators::elements(LanguageResolver::SUPPORTED),
            Generators::string()
        )->then(static function (string $persisted, string $raw): void {
            // Force the value to be unsupported.
            $invalid = in_array($raw, LanguageResolver::SUPPORTED, true) ? $raw . '_x' : $raw;

            $session = LanguageResolver::persist(['lang' => $persisted], $invalid);

            self::assertSame('en', LanguageResolver::normalize($invalid));
            self::assertSame(['lang' => $persisted], $session);
            self::assertSame($persisted, $session['lang']);
        });
    }

    /**
     * Validates: Requirements 2.5
     */
    public function testPersistThenReadReturnsLanguage(): void
    {
        // Feature: travel-rental-website, Property 3: Invalid language preserves persisted selection
        $this->limitTo(100)->forAll(
            Generators::elements(LanguageResolver::SUPPORTED)
        )->then(static function (string $lang): void {
            $session = LanguageResolver::persist([], $lang);

            self::assertSame($lang, $session['lang']);
            self::assertSame($lang, LanguageResolver::normalize($session['lang']));
        });
    }
}
