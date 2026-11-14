<?php

use App\Libraries\ContentValidator;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FieldLengthValidationTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: travel-rental-website, Property 7: Field-length validation
     * Validates: Requirements 2.8, 4.1, 4.3, 5.1, 5.3, 8.1
     */
    public function testAcceptedOnlyWhenEveryFieldWithinRange(): void
    {
        // Feature: travel-rental-website, Property 7: Field-length validation
        $kinds = [
            ContentValidator::LIMITS_NEWS,
            ContentValidator::LIMITS_FAQ,
        ];

        // Per-slot length class: mostly in-range (boundaries favoured), sometimes empty/oversized.
        $slot = Generators::frequency(
            [6, Generators::elements('min', 'low', 'mid', 'max-1', 'max')],
            [3, Generators::elements('zero', 'max+1', 'max+2', 'max+50')]
        );

        $this->limitTo(100)->forAll(
            Generators::choose(0, 1),
            Generators::elements('a', '�', '?'),
            Generators::vector(6, $slot)
        )->then(function (int $kind, string $char, array $classes) use ($kinds): void {
            $limits   = $kinds[$kind];
            $data     = [];
            $expected = [];
            $i        = 0;
            foreach ($limits as $base => $max) {
                foreach (ContentValidator::LANGUAGES as $lang) {
                    $len = match ($classes[$i++]) {
                        'min'    => 1,
                        'low'    => 2,
                        'mid'    => intdiv($max, 2),
                        'max-1'  => $max - 1,
                        'max'    => $max,
                        'zero'   => 0,
                        'max+1'  => $max + 1,
                        'max+2'  => $max + 2,
                        default  => $max + 50,
                    };
                    $key        = $base . '_' . $lang;
                    $data[$key] = str_repeat($char, $len);
                    if ($len < 1 || $len > $max) {
                        $expected[] = $key;
                    }
                }
            }

            $errors = ContentValidator::validateFieldLengths($data, $limits, true);

            $actual = array_keys($errors);
            sort($expected);
            sort($actual);

            $this->assertSame($expected, $actual);
            $this->assertSame($expected === [], $errors === []);
        });
    }
    public function testBoundaryExamples(): void
    {
        $limits = ContentValidator::LIMITS_FAQ;
        $ok     = [];
        foreach (ContentValidator::LANGUAGES as $l) {
            $ok['question_' . $l] = str_repeat('q', 500);
            $ok['answer_' . $l]   = str_repeat('a', 5000);
        }
        $this->assertSame([], ContentValidator::validateFieldLengths($ok, $limits));

        $bad                = $ok;
        $bad['question_id'] = str_repeat('q', 501);
        $bad['answer_ms']   = '';
        $this->assertSame(['question_id', 'answer_ms'], array_keys(ContentValidator::validateFieldLengths($bad, $limits)));
    }
}
