<?php

namespace App\Libraries;

/**
 * Pure helper implementing language normalization and translation fallback:
 * selected language -> English -> empty string. Never throws.
 */
final class LanguageResolver
{
    public const SUPPORTED = ['en', 'id', 'ms'];
    public const DEFAULT   = 'en';

    /** Normalize an arbitrary request value to a supported language. */
    public static function normalize(?string $lang): string
    {
        return ($lang !== null && in_array($lang, self::SUPPORTED, true)) ? $lang : self::DEFAULT;
    }

    /**
     * Pure persistence decision: returns the session array after a language
     * selection. A supported language is stored under 'lang'; anything else
     * leaves the session unchanged.
     *
     * @param array<string, mixed> $session
     * @return array<string, mixed>
     */
    public static function persist(array $session, ?string $lang): array
    {
        if ($lang !== null && in_array($lang, self::SUPPORTED, true)) {
            $session['lang'] = $lang;
        }

        return $session;
    }

    /**
     * Resolve one translatable field.
     *
     * @param array<string, mixed> $values ['en' => ..., 'id' => ..., 'ms' => ...]
     */
    public static function resolve(array $values, string $lang): string
    {
        $lang = self::normalize($lang);

        foreach ([$lang, self::DEFAULT] as $key) {
            $value = $values[$key] ?? null;
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return '';
    }
}
