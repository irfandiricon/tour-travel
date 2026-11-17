<?php

namespace App\Libraries;

/**
 * Fleet "price starting from" in whole rupiah: parse admin input and format it as "Rp 1.100.000".
 */
class PriceFormatter
{
    public const MAX = 999999999;

    /**
     * Accepts digits with optional "Rp", spaces, dots or commas as thousand separators
     * ("1100000", "1.100.000", "Rp 1,100,000"). Empty input means "no price" (null).
     *
     * @return array{ok: bool, value: int|null}
     */
    public static function parse($value): array
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return ['ok' => true, 'value' => null];
        }
        if (is_int($value)) {
            return $value >= 0 && $value <= self::MAX ? ['ok' => true, 'value' => $value] : ['ok' => false, 'value' => null];
        }
        if (! is_string($value)) {
            return ['ok' => false, 'value' => null];
        }

        $clean = trim((string) preg_replace('/^\s*rp\.?\s*/i', '', $value));
        // Plain digits, or digits grouped by 3 with dots, commas or spaces ("1.100.000").
        // "1100000.5" or "1.10.000" are rejected: a separator here never means decimals.
        if (preg_match('/^\d+$/', $clean) !== 1 && preg_match('/^\d{1,3}(?:[.,\x{00A0} ]\d{3})+$/u', $clean) !== 1) {
            return ['ok' => false, 'value' => null];
        }
        $digits = (string) preg_replace('/\D/', '', $clean);
        if (strlen(ltrim($digits, '0')) > 9) {
            return ['ok' => false, 'value' => null];
        }

        return ['ok' => true, 'value' => (int) $digits];
    }

    /** "Rp 1.100.000"; empty string for no price. */
    public static function format($value): string
    {
        if ($value === null || $value === '' || ! is_numeric($value) || (int) $value < 0) {
            return '';
        }

        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }
}