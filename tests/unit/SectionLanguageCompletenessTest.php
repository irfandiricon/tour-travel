<?php

use App\Libraries\ContentValidator;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SectionLanguageCompletenessTest extends TestCase
{
    use TestTrait;

    // Feature: travel-rental-website, Property 13: Section-content language completeness rejection
    /** Validates: Requirements 6.4 */
    public function testSectionUpdateMissingLanguagesRejectedNamingExactlyMissing(): void
    {
        $sections = array_keys(ContentValidator::SECTIONS);
        $langs    = ContentValidator::LANGUAGES;
        $blanks   = ['absent', 'null', 'empty', 'whitespace'];

        $this->limitTo(100)
            ->forAll(
                Generator\elements($sections),
                Generator\choose(0, 6), // bitmask of present languages; 7 (all) excluded
                Generator\elements($blanks),
                Generator\suchThat(
                    static fn (string $s): bool => trim($s) !== '',
                    Generator\string()
                )
            )
            ->then(function (string $section, int $mask, string $blankKind, string $content) use ($langs): void {
                $data     = [];
                $expected = [];

                foreach (ContentValidator::SECTIONS[$section] as $base) {
                    foreach ($langs as $i => $lang) {
                        $key = $base . '_' . $lang;
                        if (($mask >> $i) & 1) {
                            $data[$key] = $content;
                            continue;
                        }
                        $expected[$lang][] = $key;
                        switch ($blankKind) {
                            case 'null':
                                $data[$key] = null;
                                break;
                            case 'empty':
                                $data[$key] = '';
                                break;
                            case 'whitespace':
                                $data[$key] = "  \t ";
                                break;
                        }
                    }
                }

                $errors = ContentValidator::validateSectionLanguages($data, [$section]);

                $expectedKeys = array_merge(...array_values($expected ?: [[]]));
                $this->assertEqualsCanonicalizing($expectedKeys, array_keys($errors));
                $this->assertNotEmpty($errors);

                foreach ($langs as $lang) {
                    foreach ($expected[$lang] ?? [] as $key) {
                        $this->assertStringContainsString("language {$lang}", $errors[$key]);
                    }
                }
                // Present languages are never named in any error.
                foreach ($errors as $key => $message) {
                    $lang = substr($key, -2);
                    $this->assertArrayHasKey($lang, $expected);
                    $this->assertStringContainsString("language {$lang}", $message);
                }
            });
    }

    // Feature: travel-rental-website, Property 13: Section-content language completeness rejection
    /** Validates: Requirements 6.4 */
    public function testCompleteSectionContentIsAccepted(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\suchThat(
                    static fn (string $s): bool => trim($s) !== '',
                    Generator\string()
                )
            )
            ->then(function (string $content): void {
                $data = [];
                foreach (ContentValidator::SECTIONS as $bases) {
                    foreach ($bases as $base) {
                        foreach (ContentValidator::LANGUAGES as $lang) {
                            $data[$base . '_' . $lang] = $content;
                        }
                    }
                }
                $this->assertSame([], ContentValidator::validateSectionLanguages($data));
            });
    }
}
