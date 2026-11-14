<?php

namespace App\Libraries;

class Paginator
{
    public const DEFAULT_PER_PAGE = 10;
    public const MAX_PER_PAGE     = 100;

    /**
     * Slice an ordered list and return it with pagination metadata.
     *
     * @param array           $items   Ordered dataset
     * @param int|string|null $page    Requested page (minimum 1)
     * @param int|string|null $perPage Items per page (1..100, default 10)
     *
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public static function paginate(array $items, $page = 1, $perPage = null): array
    {
        $page    = self::toPositiveInt($page) ?? 1;
        $perPage = self::toPositiveInt($perPage) ?? self::DEFAULT_PER_PAGE;
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $items = array_values($items);
        $total = count($items);
        $data  = array_slice($items, ($page - 1) * $perPage, $perPage);

        return [
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Returns an int >= 1 for valid numeric input, otherwise null.
     */
    private static function toPositiveInt($value): ?int
    {
        if ($value === null || $value === '' || is_bool($value) || ! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int >= 1 ? $int : null;
    }
}
