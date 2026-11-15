<?php

namespace App\Libraries;

use CodeIgniter\Model;

/**
 * Keyword search for the admin tables. The term is trimmed, inner spaces collapsed and cut to
 * MAX_LENGTH; it matches when ANY of the given columns contains it (case-insensitive collation).
 * The value is bound by like(); % _ and ! are escaped here with escapeLikeString() (like() itself only
 * adds the ESCAPE '!' clause), so they match literally instead of acting as wildcards.
 */
class AdminSearch
{
    public const MAX_LENGTH = 100;

    public static function term($value): string
    {
        if (! is_string($value)) {
            return '';
        }
        $term = trim((string) preg_replace('/\s+/u', ' ', $value));

        return mb_substr($term, 0, self::MAX_LENGTH);
    }

    /**
     * Add "(col1 LIKE %term% OR col2 LIKE %term% ...)" to the model's query. No-op for an empty term.
     *
     * @param list<string> $columns
     */
    public static function apply(Model $model, string $term, array $columns): Model
    {
        if ($term === '' || $columns === []) {
            return $model;
        }
        $escaped = \Config\Database::connect()->escapeLikeString($term);
        $model->groupStart();
        foreach (array_values($columns) as $i => $column) {
            $i === 0 ? $model->like($column, $escaped) : $model->orLike($column, $escaped);
        }
        $model->groupEnd();

        return $model;
    }
}