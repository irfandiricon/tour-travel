<?php

namespace App\Libraries;

/**
 * Pure content validators (no DB, no framework state).
 *
 * Translatable fields are stored as sibling keys `<base>_en`, `<base>_id`, `<base>_ms`.
 * Every method returns a field-keyed error map: [ 'title_en' => 'message', ... ].
 * An EMPTY array means the input is valid. Callers (api/v1 controllers) may merge
 * several maps with array_merge() and answer 422 when the result is non-empty.
 *
 * Length is measured in characters (mb_strlen, UTF-8). A value is "empty" when it is
 * missing, null, non-scalar, or a string that is empty after trim().
 */
class ContentValidator
{
    public const LANGUAGES        = ['en', 'id', 'ms'];
    public const DEFAULT_LANGUAGE = 'en';

    /** Max lengths per base field (Req 2.8, 4.1, 4.3, 5.1, 5.3, 8.1). */
    public const LIMITS_NEWS        = ['title' => 200, 'body' => 20000];
    public const LIMITS_FAQ         = ['question' => 500, 'answer' => 5000];

    /** Landing page sections and their translatable base fields (Req 6.3, 6.4). */
    public const SECTIONS = [
        'hero'    => ['hero_title', 'hero_subtitle'],
        'about'   => ['about_body'],
        'gallery' => ['gallery_title'],
        'contact' => ['contact_body'],
    ];

    /**
     * Require the default-language (en) value of each translatable base field (Req 2.9, 4.1, 5.1, 8.1, 8.6).
     *
     * @param array<string,mixed> $data       Submitted record, e.g. ['title_en' => 'x', ...]
     * @param string[]            $baseFields Base names, e.g. ['title', 'body']
     * @return array<string,string> e.g. ['title_en' => 'title_en is required (default language en)']
     */
    public static function validateRequiredEn(array $data, array $baseFields): array
    {
        $errors = [];
        foreach ($baseFields as $base) {
            $key = $base . '_' . self::DEFAULT_LANGUAGE;
            if (self::isEmpty($data[$key] ?? null)) {
                $errors[$key] = "{$key} is required (default language " . self::DEFAULT_LANGUAGE . ')';
            }
        }

        return $errors;
    }

    /**
     * Validate each translatable field is 1..max chars in en, id, ms.
     * Missing/empty values are errors unless $requireAllLanguages is false, in which
     * case only `_en` must be non-empty and id/ms may be empty (but not oversized).
     * Oversized values are always errors.
     *
     * @param array<string,mixed> $data   Submitted record
     * @param array<string,int>   $limits base field => max length, e.g. self::LIMITS_NEWS
     * @param bool                $requireAllLanguages true = 1..max required in en/id/ms
     * @return array<string,string> field-keyed errors (empty when valid)
     */
    public static function validateFieldLengths(array $data, array $limits, bool $requireAllLanguages = true): array
    {
        $errors = [];
        foreach ($limits as $base => $max) {
            foreach (self::LANGUAGES as $lang) {
                $key   = $base . '_' . $lang;
                $value = $data[$key] ?? null;

                if (self::isEmpty($value)) {
                    if ($requireAllLanguages || $lang === self::DEFAULT_LANGUAGE) {
                        $errors[$key] = "{$key} is required (1 to {$max} characters)";
                    }
                    continue;
                }

                if (mb_strlen((string) $value, 'UTF-8') > $max) {
                    $errors[$key] = "{$key} must not exceed {$max} characters";
                }
            }
        }

        return $errors;
    }

    /**
     * Require hero/about/gallery/contact content in all of en, id, ms; each missing
     * language is named in its own error (Req 6.4).
     *
     * @param array<string,mixed>      $data     Submitted site configuration
     * @param string[]|null            $sections Sections being updated (default: all of self::SECTIONS)
     * @param array<string,string[]>|null $map   section => base fields (default: self::SECTIONS)
     * @return array<string,string> e.g. ['hero_title_id' => 'hero_title: missing content for language id']
     */
    public static function validateSectionLanguages(array $data, ?array $sections = null, ?array $map = null): array
    {
        $map      = $map ?? self::SECTIONS;
        $sections = $sections ?? array_keys($map);
        $errors   = [];

        foreach ($sections as $section) {
            foreach ($map[$section] ?? [] as $base) {
                foreach (self::LANGUAGES as $lang) {
                    $key = $base . '_' . $lang;
                    if (self::isEmpty($data[$key] ?? null)) {
                        $errors[$key] = "{$section} section ({$base}): missing content for language {$lang}";
                    }
                }
            }
        }

        return $errors;
    }

    /** SEO settings base fields and their max lengths (Req 11.17, 11.18). */
    public const LIMITS_SEO = ['site_name' => 150, 'meta_description' => 320, 'meta_keywords' => 255];

    /**
     * Validate SEO settings (Req 11.18, 11.19). For each SEO field group, when a value
     * is provided in any language the English value is required; every value must be
     * within its max length. All-empty SEO settings are valid.
     *
     * @param array<string,mixed> $data Merged site configuration
     * @return array<string,string> field-keyed errors (empty when valid)
     */
    public static function validateSeoSettings(array $data): array
    {
        $errors = [];
        foreach (self::LIMITS_SEO as $base => $max) {
            $anyProvided = false;
            foreach (self::LANGUAGES as $lang) {
                $key   = $base . '_' . $lang;
                $value = $data[$key] ?? null;
                if (self::isEmpty($value)) {
                    continue;
                }
                $anyProvided = true;
                if (mb_strlen((string) $value, 'UTF-8') > $max) {
                    $errors[$key] = "{$key} must not exceed {$max} characters";
                }
            }

            $enKey = $base . '_' . self::DEFAULT_LANGUAGE;
            if ($anyProvided && self::isEmpty($data[$enKey] ?? null)) {
                $errors[$enKey] = "{$enKey} is required (default language " . self::DEFAULT_LANGUAGE . ') when SEO values are provided';
            }
        }

        $og = $data['og_image'] ?? null;
        if (! self::isEmpty($og) && mb_strlen((string) $og, 'UTF-8') > 500) {
            $errors['og_image'] = 'og_image must not exceed 500 characters';
        }

        return $errors;
    }

    /** Optional About-section heading (per language). */
    public const LIMITS_ABOUT = ['about_title' => 150];

    /**
     * Validate the optional About extras: heading length per language and image path length.
     * Everything here is optional, so empty values are valid.
     *
     * @param array<string,mixed> $data Merged site configuration
     * @return array<string,string> field-keyed errors (empty when valid)
     */
    public static function validateAboutSettings(array $data): array
    {
        $errors = [];
        foreach (self::LIMITS_ABOUT as $base => $max) {
            foreach (self::LANGUAGES as $lang) {
                $key   = $base . '_' . $lang;
                $value = $data[$key] ?? null;
                if (! self::isEmpty($value) && mb_strlen((string) $value, 'UTF-8') > $max) {
                    $errors[$key] = "{$key} must not exceed {$max} characters";
                }
            }
        }

        $image = $data['about_image'] ?? null;
        if (! self::isEmpty($image) && mb_strlen((string) $image, 'UTF-8') > 500) {
            $errors['about_image'] = 'about_image must not exceed 500 characters';
        }

        return $errors;
    }

    /**
     * Validate a booking click payload (Req 12.9-12.11): positive numeric fleet_id,
     * fleet_name 1-200 chars, lang in en/id/ms.
     *
     * @param array<string,mixed> $data
     * @return array<string,string> field-keyed errors (empty when valid)
     */
    public static function validateBookingClick(array $data): array
    {
        $errors = [];

        $id = $data['fleet_id'] ?? null;
        if (! (is_int($id) || is_string($id)) || preg_match('/^[0-9]+$/', trim((string) $id)) !== 1 || (int) trim((string) $id) < 1) {
            $errors['fleet_id'] = 'fleet_id must be a positive number';
        }

        $name = $data['fleet_name'] ?? null;
        if (! is_string($name) || trim($name) === '') {
            $errors['fleet_name'] = 'fleet_name is required';
        } elseif (mb_strlen(trim($name), 'UTF-8') > 200) {
            $errors['fleet_name'] = 'fleet_name must not exceed 200 characters';
        }

        $lang = $data['lang'] ?? null;
        if (! is_string($lang) || ! in_array($lang, self::LANGUAGES, true)) {
            $errors['lang'] = 'lang must be one of: ' . implode(', ', self::LANGUAGES);
        }

        return $errors;
    }

    /**
     * @param mixed $value
     */
    private static function isEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }
        if (! is_scalar($value)) {
            return true;
        }

        return trim((string) $value) === '';
    }
}
