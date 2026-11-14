<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'Helpers/ui_helper.php';

/**
 * @internal
 */
final class UiAdminDatePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Validates: Requirements 7.9, 13.9
     */
    public function testAdminDateFormatIsParseableAndRoundTrips(): void
    {
        // Feature: ui-redesign-reference, Property 5: Format tanggal header admin dapat di-parse
        $this->limitTo(100)->forAll(
            Generators::choose(1970, 2100),
            Generators::choose(1, 12),
            Generators::choose(1, 31)
        )->then(static function (int $year, int $month, int $day): void {
            if (! checkdate($month, $day, $year)) {
                return;
            }

            $date   = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->setDate($year, $month, $day)->setTime(13, 5, 9);
            $result = ui_admin_date($date);

            // Pola: NamaHari, dd NamaBulan yyyy (bahasa Inggris).
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+, \d{2} [A-Z][a-z]+ \d{4}$/', $result);

            $parsed = DateTimeImmutable::createFromFormat('!l, d F Y', $result, new DateTimeZone('UTC'));
            self::assertNotFalse($parsed);
            self::assertSame($date->format('Y-m-d'), $parsed->format('Y-m-d'));
            self::assertSame($date->format('l'), $parsed->format('l'));
        });
    }

    /**
     * Validates: Requirements 7.9
     */
    public function testKnownDate(): void
    {
        // Feature: ui-redesign-reference, Property 5: Format tanggal header admin dapat di-parse
        self::assertSame('Monday, 12 May 2025', ui_admin_date(new DateTimeImmutable('2025-05-12 08:00:00')));
    }
}
