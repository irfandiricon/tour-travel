<?php

declare(strict_types=1);

namespace App\Libraries;

use DateInterval;
use DateTimeImmutable;

/**
 * Pure dashboard statistics helpers. Operates on plain arrays only: no database,
 * HTTP or framework service access.
 */
final class DashboardStats
{
    /**
     * Build a complete, ascending list of daily counts ending at $endDate
     * (inclusive) and spanning $days days. Missing dates are filled with 0.
     *
     * @param array<string,int|float|string> $countsByDate map of 'Y-m-d' => count
     * @return list<array{date:string,count:int}>
     */
    public static function fillDateRange(array $countsByDate, string $endDate, int $days): array
    {
        $days = max(0, $days);
        $result = [];
        if ($days === 0) {
            return $result;
        }

        $end = new DateTimeImmutable($endDate);
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $end->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
            $result[] = [
                'date'  => $date,
                'count' => isset($countsByDate[$date]) ? max(0, (int) $countsByDate[$date]) : 0,
            ];
        }

        return $result;
    }

    /**
     * Convert counts into percentages with one decimal using the largest
     * remainder method. The result sums to exactly 100.0, or every value is
     * 0.0 when the total is 0. Keys and order are preserved.
     *
     * @param array<string|int,int|float> $counts
     * @return array<string|int,float>
     */
    public static function distribution(array $counts): array
    {
        $clean = [];
        $total = 0;
        foreach ($counts as $key => $value) {
            $n = max(0, (int) $value);
            $clean[$key] = $n;
            $total += $n;
        }

        if ($total === 0) {
            return array_map(static fn ($v): float => 0.0, $clean);
        }

        // Work in tenths of a percent so the sum must equal 1000.
        $tenths     = [];
        $remainders = [];
        $assigned   = 0;
        foreach ($clean as $key => $n) {
            $scaled          = $n * 1000;
            $tenths[$key]    = intdiv($scaled, $total);
            $remainders[$key] = $scaled % $total;
            $assigned        += $tenths[$key];
        }

        $left = 1000 - $assigned;
        if ($left > 0) {
            $keys = array_keys($remainders);
            $pos  = array_flip($keys);
            usort($keys, static function ($a, $b) use ($remainders, $pos): int {
                return [$remainders[$b], $pos[$a]] <=> [$remainders[$a], $pos[$b]];
            });
            for ($i = 0; $i < $left; $i++) {
                $tenths[$keys[$i]]++;
            }
        }

        $result = [];
        foreach ($clean as $key => $_) {
            $result[$key] = $tenths[$key] / 10;
        }

        return $result;
    }

    /**
     * Remove rows flagged as bots (is_bot truthy). Rows keep their order.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    public static function excludeBots(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (empty($row['is_bot'])) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Count distinct (visit_date, visitor_hash) pairs for today, the last 7
     * days and the last 30 days (windows inclusive of $today).
     *
     * @param list<array<string,mixed>> $rows rows with visit_date and visitor_hash
     * @return array{today:int,days7:int,days30:int}
     */
    public static function visitorTotals(array $rows, string $today): array
    {
        $todayDt = new DateTimeImmutable($today);
        $start7  = $todayDt->sub(new DateInterval('P6D'))->format('Y-m-d');
        $start30 = $todayDt->sub(new DateInterval('P29D'))->format('Y-m-d');
        $todayStr = $todayDt->format('Y-m-d');

        $sets = ['today' => [], 'days7' => [], 'days30' => []];
        foreach ($rows as $row) {
            $date = (string) ($row['visit_date'] ?? '');
            $hash = (string) ($row['visitor_hash'] ?? '');
            if ($date === '' || $date > $todayStr) {
                continue;
            }
            $pair = $date . '|' . $hash;
            if ($date >= $start30) {
                $sets['days30'][$pair] = true;
            }
            if ($date >= $start7) {
                $sets['days7'][$pair] = true;
            }
            if ($date === $todayStr) {
                $sets['today'][$pair] = true;
            }
        }

        return [
            'today'  => count($sets['today']),
            'days7'  => count($sets['days7']),
            'days30' => count($sets['days30']),
        ];
    }

    /**
     * Top N entries by count descending; ties are broken by label ascending.
     * Entries with a count of 0 are dropped.
     *
     * @param array<string|int,int|float> $counts label => count
     * @return list<array{label:string,count:int}>
     */
    public static function topN(array $counts, int $n): array
    {
        $items = [];
        foreach ($counts as $label => $count) {
            $c = (int) $count;
            if ($c > 0) {
                $items[] = ['label' => (string) $label, 'count' => $c];
            }
        }

        usort($items, static function (array $a, array $b): int {
            return [$b['count'], $a['label']] <=> [$a['count'], $b['label']];
        });

        return array_slice($items, 0, max(0, $n));
    }
}
