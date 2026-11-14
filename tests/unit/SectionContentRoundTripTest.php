<?php

use App\Controllers\ApiV1\SiteConfigController;
use App\Libraries\ContentValidator;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SectionContentRoundTripTest extends TestCase
{
    use TestTrait;

    /**
     * In-memory double of the persistence step in SiteConfigController::save():
     * run prepareSave, then merge the record over the stored row.
     *
     * @param array<string,mixed>|null $store
     * @param array<string,mixed>      $input
     */
    private function save(?array &$store, array $input): array
    {
        $result = SiteConfigController::prepareSave($store, $input);
        if (! $result['errors']) {
            $store = array_merge($store ?? [], $result['record']);
        }

        return $result['errors'];
    }

    // Feature: travel-rental-website, Property 12: Section-content all-languages save round trip
    /** Validates: Requirements 6.3 */
    public function testSavingAllThreeLanguagesThenReadingBackReturnsSameValues(): void
    {
        $nonBlank = static fn (): Generator\SuchThatGenerator => Generator\suchThat(
            static fn (string $s): bool => trim($s) !== '',
            Generator\string()
        );

        $this->limitTo(100)
            ->forAll(
                Generator\elements(array_keys(ContentValidator::SECTIONS)),
                $nonBlank(),
                $nonBlank(),
                $nonBlank()
            )
            ->then(function (string $section, string $en, string $id, string $ms): void {
                // Start from a complete stored config so unrelated sections remain valid.
                $store = null;
                $base  = [];
                foreach (ContentValidator::SECTIONS as $bases) {
                    foreach ($bases as $field) {
                        foreach (ContentValidator::LANGUAGES as $lang) {
                            $base[$field . '_' . $lang] = 'seed';
                        }
                    }
                }
                $this->assertSame([], $this->save($store, $base));

                $values = ['en' => $en, 'id' => $id, 'ms' => $ms];
                $input  = [];
                foreach (ContentValidator::SECTIONS[$section] as $field) {
                    foreach ($values as $lang => $value) {
                        $input[$field . '_' . $lang] = $value;
                    }
                }

                $this->assertSame([], $this->save($store, $input));

                // Read back from the double.
                foreach (ContentValidator::SECTIONS[$section] as $field) {
                    foreach ($values as $lang => $value) {
                        $this->assertSame(trim($value), $store[$field . '_' . $lang]);
                    }
                }
            });
    }
}
