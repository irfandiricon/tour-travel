<?php

use App\Libraries\LanguageResolver;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LanguageResolverFallbackTest extends TestCase
{
    use TestTrait;

    // Feature: travel-rental-website, Property 2: Resolver fallback chain and normalization
    /** Validates: Requirements 1.3, 2.2, 2.6, 2.7 */
    public function testNormalizeMapsUnsupportedToEnglishAndKeepsSupported(): void
    {
        $this->limitTo(100)
            ->forAll(Generator\oneOf(
                Generator\constant(null),
                Generator\elements(['en', 'id', 'ms']),
                Generator\string(),
                Generator\elements(['EN', 'fr', '', ' id', 'zh'])
            ))
            ->then(function (?string $input): void {
                $expected = in_array($input, ['en', 'id', 'ms'], true) ? $input : 'en';
                $this->assertSame($expected, LanguageResolver::normalize($input));
            });
    }

    // Feature: travel-rental-website, Property 2: Resolver fallback chain and normalization
    /** Validates: Requirements 1.3, 2.2, 2.6, 2.7 */
    public function testResolveFollowsSelectedThenEnglishThenEmpty(): void
    {
        $value = Generator\oneOf(
            Generator\constant(null),
            Generator\constant(''),
            Generator\string()
        );

        $this->limitTo(100)
            ->forAll(
                Generator\oneOf(Generator\elements(['en', 'id', 'ms']), Generator\string()),
                $value,
                $value,
                $value
            )
            ->then(function (string $lang, ?string $en, ?string $id, ?string $ms): void {
                $map = [];
                foreach (['en' => $en, 'id' => $id, 'ms' => $ms] as $k => $v) {
                    if ($v !== null) {
                        $map[$k] = $v;
                    }
                }
                $selected = LanguageResolver::normalize($lang);
                $sel      = $map[$selected] ?? '';
                $eng      = $map['en'] ?? '';
                $expected = $sel !== '' ? $sel : ($eng !== '' ? $eng : '');

                $this->assertSame($expected, LanguageResolver::resolve($map, $lang));
            });
    }
}
