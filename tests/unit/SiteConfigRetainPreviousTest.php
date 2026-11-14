<?php

use App\Controllers\ApiV1\SiteConfigController;
use App\Libraries\ContentValidator;
use App\Libraries\WhatsAppLinkBuilder;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * In-memory stand-in for the site_config table: persists only what prepareSave approves.
 */
final class InMemorySiteConfigStore
{
    /** @var array<string,mixed>|null */
    public ?array $row;

    /** @param array<string,mixed>|null $row */
    public function __construct(?array $row)
    {
        $this->row = $row;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,string> errors
     */
    public function save(array $input): array
    {
        $result = SiteConfigController::prepareSave($this->row, $input);
        if ($result['errors'] === []) {
            $this->row = array_merge($this->row ?? [], $result['record']);
        }

        return $result['errors'];
    }
}

/**
 * @internal
 */
final class SiteConfigRetainPreviousTest extends TestCase
{
    use TestTrait;

    /** @return string[] every language-required site-config field */
    private function requiredKeys(): array
    {
        $keys = [];
        foreach (ContentValidator::SECTIONS as $bases) {
            foreach ($bases as $base) {
                foreach (ContentValidator::LANGUAGES as $lang) {
                    $keys[] = $base . '_' . $lang;
                }
            }
        }

        return $keys;
    }

    // Feature: travel-rental-website, Property 11: Site-config invalid-field rejection retains previous configuration
    /** Validates: Requirements 6.2 */
    public function testInvalidSaveNamesEachInvalidFieldAndRetainsPreviousConfig(): void
    {
        $required = $this->requiredKeys();
        $blanks   = ['', "  \t ", null];

        $this->limitTo(100)
            ->forAll(
                Generator\suchThat(
                    static fn (string $s): bool => trim($s) !== '',
                    Generator\string()
                ),
                Generator\subset($required),
                Generator\elements($blanks),
                Generator\bool(),
                Generator\suchThat(
                    static fn (string $s): bool => trim($s) !== '' && ! WhatsAppLinkBuilder::isValidNumber(trim($s)),
                    Generator\string()
                )
            )
            ->then(function (string $content, array $invalidKeys, $blank, bool $badNumber, string $number) use ($required): void {
                // Guarantee at least one invalid field.
                if ($invalidKeys === []) {
                    $badNumber = true;
                }

                $previous = ['id' => 1, 'whatsapp_number' => '+628123456789'];
                foreach ($required as $key) {
                    $previous[$key] = $content;
                }

                $store = new InMemorySiteConfigStore($previous);

                $submission = $previous;
                unset($submission['id']);
                foreach ($invalidKeys as $key) {
                    $submission[$key] = $blank;
                }
                if ($badNumber) {
                    $submission['whatsapp_number'] = $number;
                }

                $errors = $store->save($submission);

                $expected = $invalidKeys;
                if ($badNumber) {
                    $expected[] = 'whatsapp_number';
                }

                // Each invalid field is named, and nothing else.
                $this->assertEqualsCanonicalizing($expected, array_keys($errors));

                // Rejected save yields an empty record and leaves the stored config identical.
                $result = SiteConfigController::prepareSave($previous, $submission);
                $this->assertSame([], $result['record']);
                $this->assertSame($previous, $store->row);
            });
    }

    // Feature: travel-rental-website, Property 11: Site-config invalid-field rejection retains previous configuration
    /** Validates: Requirements 6.2 */
    public function testRejectedFirstSaveLeavesNoConfig(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\suchThat(
                    static fn (string $s): bool => trim($s) !== '',
                    Generator\string()
                )
            )
            ->then(function (string $content): void {
                $store  = new InMemorySiteConfigStore(null);
                $errors = $store->save(['hero_title_en' => $content]);

                $this->assertNotEmpty($errors);
                $this->assertNull($store->row);
            });
    }
}
