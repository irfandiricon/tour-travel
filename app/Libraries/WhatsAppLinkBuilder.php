<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Pure helper for E.164 validation and wa.me click-to-chat link building.
 */
final class WhatsAppLinkBuilder
{
    /** E.164: leading '+', 8-15 digits. The D modifier rejects a trailing newline. */
    private const E164_PATTERN = '/^\+\d{8,15}$/D';

    /** Maximum prefill length in characters (Req 7.6). */
    private const MAX_PREFILL = 200;

    public static function isValidNumber(?string $number): bool
    {
        if ($number === null) {
            return false;
        }

        return preg_match(self::E164_PATTERN, $number) === 1;
    }

    /**
     * Returns https://wa.me/<digits>[?text=<encoded prefill>] or null when the number is invalid/empty.
     */
    public static function build(?string $number, string $prefill = ''): ?string
    {
        if (! self::isValidNumber($number)) {
            return null;
        }

        $digits = ltrim($number, '+');
        $msg    = mb_substr($prefill, 0, self::MAX_PREFILL);
        $query  = $msg !== '' ? '?text=' . rawurlencode($msg) : '';

        return 'https://wa.me/' . $digits . $query;
    }
}
