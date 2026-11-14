<?php

use App\Libraries\ContentValidator;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RequiredEnValidationTest extends TestCase
{
    use TestTrait;

    private const POOL = ['title', 'body', 'question', 'answer'];

    /**
     * Builds a record from a field mask and per-field en states.
     * States: 0 valid, 1 key missing, 2 null, 3 empty string, 4 whitespace only, 5 non-scalar.
     * id/ms are always filled so they must never satisfy the en requirement.
     *
     * @return array{0: array<string,mixed>, 1: string[], 2: string[]} [data, baseFields, expectedMissingKeys]
     */
    private function build(int $mask, array $states, string $text): array
    {
        $bases = [];
        foreach (self::POOL as $i => $base) {
            if ($mask & (1 << $i)) {
                $bases[] = $base;
            }
        }

        $data     = [];
        $expected = [];
        foreach ($bases as $i => $base) {
            $state = $states[$i];
            $key   = $base . '_en';
            $data[$base . '_id'] = 'id ' . $text;
            $data[$base . '_ms'] = 'ms ' . $text;

            switch ($state) {
                case 0:
                    $data[$key] = 'x' . $text;
                    break;

                case 2:
                    $data[$key] = null;
                    break;

                case 3:
                    $data[$key] = '';
                    break;

                case 4:
                    $data[$key] = " \t\n ";
                    break;

                case 5:
                    $data[$key] = ['not', 'scalar'];
                    break;
            }
            if ($state !== 0) {
                $expected[] = $key;
            }
        }

        return [$data, $bases, $expected];
    }

    /**
     * Validates: Requirements 2.9, 4.5, 5.5, 8.6
     */
    public function testMissingOrEmptyEnglishIsRejectedAndNamed(): void
    {
        // Feature: travel-rental-website, Property 6: Required-field (mandatory-en) rejection
        $this->limitTo(150)->forAll(
            Generators::choose(1, 15),
            Generators::vector(4, Generators::choose(0, 5)),
            Generators::string()
        )->then(function (int $mask, array $states, string $text): void {
            [$data, $bases, $expected] = $this->build($mask, $states, $text);

            $errors = ContentValidator::validateRequiredEn($data, $bases);

            $this->assertEqualsCanonicalizing($expected, array_keys($errors));
            foreach ($errors as $key => $message) {
                $this->assertIsString($message);
                $this->assertStringContainsString($key, $message);
            }
            $this->assertSame($expected === [], $errors === []);
        });
    }
}
