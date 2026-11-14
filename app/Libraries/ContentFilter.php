<?php

declare(strict_types=1);

namespace App\Libraries;

use DateTimeInterface;

/**
 * Pure helpers for filtering/ordering public content. Items may be arrays or objects.
 */
class ContentFilter
{
    /**
     * Keep only items whose status is exactly 'published' (order preserved).
     *
     * @param iterable<mixed> $items
     * @return list<mixed>
     */
    public static function publishedOnly(iterable $items): array
    {
        $out = [];

        foreach ($items as $item) {
            if (self::field($item, 'status') === 'published') {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * One image source per item: image_path when non-empty, otherwise the placeholder.
     *
     * @param iterable<mixed> $items
     * @return list<string>
     */
    public static function fleetImageSources(iterable $items, string $placeholder): array
    {
        $out = [];

        foreach ($items as $item) {
            $path  = self::field($item, 'image_path');
            $out[] = (is_string($path) && trim($path) !== '') ? $path : $placeholder;
        }

        return $out;
    }

    /**
     * Published news with publish_date <= $now, newest first.
     *
     * @param iterable<mixed>                  $items
     * @param DateTimeInterface|string|int     $now
     * @return list<mixed>
     */
    public static function publishedNewsDescending(iterable $items, $now): array
    {
        $nowTs = self::toTimestamp($now);
        $rows  = [];

        foreach (self::publishedOnly($items) as $item) {
            $ts = self::toTimestamp(self::field($item, 'publish_date'));

            if ($ts !== null && $nowTs !== null && $ts <= $nowTs) {
                $rows[] = [$ts, $item];
            }
        }

        usort($rows, static fn (array $a, array $b): int => $b[0] <=> $a[0]);

        return array_map(static fn (array $r) => $r[1], $rows);
    }

    /**
     * @param mixed $item
     * @return mixed
     */
    private static function field($item, string $key)
    {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        if (is_object($item)) {
            return $item->{$key} ?? null;
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private static function toTimestamp($value): ?int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $ts = strtotime($value);

            return $ts === false ? null : $ts;
        }

        return null;
    }
}
