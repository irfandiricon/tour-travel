<?php

namespace App\Libraries;

/**
 * Pure rules for customer reviews: validation, star text and the average shown on the landing page.
 */
class TestimonialValidator
{
    public const NAME_MAX   = 100;
    public const REVIEW_MAX = 500;
    public const MIN_RATING = 1;
    public const MAX_RATING = 5;

    /** English is required (site default); Indonesian and Malay are optional and fall back to English. */
    public const REVIEW_LANGS = ['en', 'id', 'ms'];

    /**
     * @param array<string,mixed> $data customer_name, rating, review_en (required), review_id, review_ms
     *
     * @return array<string,string> field => message; empty when valid
     */
    public static function validate(array $data): array
    {
        $errors = [];

        $name = is_scalar($data['customer_name'] ?? null) ? trim((string) $data['customer_name']) : '';
        if ($name === '') {
            $errors['customer_name'] = 'Customer name is required.';
        } elseif (mb_strlen($name) > self::NAME_MAX) {
            $errors['customer_name'] = 'Customer name must be at most ' . self::NAME_MAX . ' characters.';
        }

        if (self::rating($data['rating'] ?? null) === null) {
            $errors['rating'] = 'Choose a rating from 1 to 5 stars.';
        }

        foreach (self::REVIEW_LANGS as $lang) {
            $field  = 'review_' . $lang;
            $review = is_scalar($data[$field] ?? null) ? trim((string) $data[$field]) : '';
            if ($review === '' && $lang === 'en') {
                $errors[$field] = 'Review (EN) is required.';
            } elseif (mb_strlen($review) > self::REVIEW_MAX) {
                $errors[$field] = 'Review (' . strtoupper($lang) . ') must be at most ' . self::REVIEW_MAX . ' characters.';
            }
        }

        return $errors;
    }

    /** Whole number 1-5, or null for anything else ("4.5", "6", "abc", ...). */
    public static function rating($value): ?int
    {
        if (is_bool($value) || (! is_int($value) && ! is_string($value))) {
            return null;
        }
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => self::MIN_RATING, 'max_range' => self::MAX_RATING]]);

        return $int === false ? null : $int;
    }

    /** "★★★★☆" for 4 (used in the admin list). */
    public static function stars($rating): string
    {
        $n = self::rating(is_int($rating) ? $rating : (string) $rating) ?? 0;

        return str_repeat("\u{2605}", $n) . str_repeat("\u{2606}", self::MAX_RATING - $n);
    }

    /**
     * Average of the valid ratings, rounded to one decimal, and how many there are.
     *
     * @param iterable<array<string,mixed>> $rows
     *
     * @return array{average: float, count: int}|null null when there is no valid rating
     */
    public static function summary(iterable $rows): ?array
    {
        $sum   = 0;
        $count = 0;
        foreach ($rows as $row) {
            $r = self::rating(is_array($row) ? ($row['rating'] ?? null) : null);
            if ($r !== null) {
                $sum += $r;
                $count++;
            }
        }

        return $count === 0 ? null : ['average' => round($sum / $count, 1), 'count' => $count];
    }
}