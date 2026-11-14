<?php

use App\Libraries\DashboardStats;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DashboardStatsPropertyTest extends TestCase
{
    use TestTrait;

    // Feature: travel-rental-website, Property 17: Date-range zero-fill completeness and ordering
    /** Validates: Requirements 12.17, 12.25 */
    public function testFillDateRangeIsCompleteOrderedAndZeroFilled(): void
    {
        $base = new DateTimeImmutable('2020-01-01');

        $this->limitTo(100)
            ->forAll(
                Generator\choose(0, 3000),          // end date offset in days from base
                Generator\choose(1, 60),            // range length
                Generator\vector(40, Generator\choose(-70, 5)), // candidate day offsets relative to end
                Generator\vector(40, Generator\choose(0, 500)), // counts
                Generator\vector(40, Generator\bool())          // whether to include the entry
            )
            ->then(function (int $endOffset, int $days, array $offsets, array $counts, array $include) use ($base): void {
                $end    = $base->add(new DateInterval('P' . $endOffset . 'D'));
                $endStr = $end->format('Y-m-d');

                $map = [];
                foreach ($offsets as $i => $off) {
                    if (! $include[$i]) {
                        continue;
                    }
                    $d = $off >= 0
                        ? $end->add(new DateInterval('P' . $off . 'D'))
                        : $end->sub(new DateInterval('P' . (-$off) . 'D'));
                    $map[$d->format('Y-m-d')] = $counts[$i];
                }

                $result = DashboardStats::fillDateRange($map, $endStr, $days);

                // Completeness: exactly $days entries.
                $this->assertCount($days, $result);

                // Ordering: strictly ascending consecutive dates ending at $endStr.
                $this->assertSame($endStr, $result[$days - 1]['date']);
                $expectedStart = $end->sub(new DateInterval('P' . ($days - 1) . 'D'))->format('Y-m-d');
                $this->assertSame($expectedStart, $result[0]['date']);

                for ($i = 1; $i < $days; $i++) {
                    $prev = new DateTimeImmutable($result[$i - 1]['date']);
                    $this->assertSame(
                        $prev->add(new DateInterval('P1D'))->format('Y-m-d'),
                        $result[$i]['date']
                    );
                }

                // Zero-fill: present dates keep their count, missing dates are 0.
                foreach ($result as $row) {
                    $this->assertSame($map[$row['date']] ?? 0, $row['count']);
                }
            });
    }

    // Feature: travel-rental-website, Property 18: Distribution percentages sum to 100 or 0
    /** Validates: Requirements 12.18, 12.19 */
    public function testDistributionSumsToOneHundredOrZero(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\vector(6, Generator\choose(0, 10000))
            )
            ->then(function (array $values): void {
                // Variable-length input: use a random-size prefix derived from the data.
                $size   = 1 + (array_sum($values) % 6);
                $counts = [];
                foreach (array_slice($values, 0, $size) as $i => $v) {
                    $counts['k' . $i] = $v;
                }

                $result = DashboardStats::distribution($counts);

                $this->assertSame(array_keys($counts), array_keys($result));

                $sumTenths = 0;
                foreach ($result as $pct) {
                    $this->assertGreaterThanOrEqual(0.0, $pct);
                    $this->assertLessThanOrEqual(100.0, $pct);
                    $sumTenths += (int) round($pct * 10);
                }

                $expected = array_sum($counts) === 0 ? 0 : 1000;
                $this->assertSame($expected, $sumTenths);
            });
    }

    // Feature: travel-rental-website, Property 21: Bots never count as visitors
    /** Validates: Requirements 12.6 */
    public function testBotsNeverCountAsVisitors(): void
    {
        $today = '2024-06-30';
        $base  = new DateTimeImmutable($today);

        $this->limitTo(100)
            ->forAll(
                Generator\vector(30, Generator\choose(0, 40)),  // days before today
                Generator\vector(30, Generator\choose(0, 5)),   // visitor hash id
                Generator\vector(30, Generator\bool()),         // is_bot flag
                Generator\choose(0, 30)                         // number of rows used
            )
            ->then(function (array $ages, array $ids, array $bots, int $size) use ($base, $today): void {
                $all = [];
                $humans = [];
                for ($i = 0; $i < $size; $i++) {
                    $row = [
                        'visit_date'   => $base->sub(new DateInterval('P' . $ages[$i] . 'D'))->format('Y-m-d'),
                        'visitor_hash' => hash('sha256', 'v' . $ids[$i]),
                        'is_bot'       => $bots[$i] ? 1 : 0,
                    ];
                    $all[] = $row;
                    if (! $bots[$i]) {
                        $humans[] = $row;
                    }
                }

                $filtered = DashboardStats::excludeBots($all);

                // No bot row survives; every non-bot row is kept in order.
                foreach ($filtered as $row) {
                    $this->assertEmpty($row['is_bot']);
                }
                $this->assertSame($humans, $filtered);

                // Totals ignore bots entirely.
                $this->assertSame(
                    DashboardStats::visitorTotals($humans, $today),
                    DashboardStats::visitorTotals($filtered, $today)
                );

                // A dataset made only of bots yields zero visitors.
                $onlyBots = array_map(static fn (array $r): array => array_merge($r, ['is_bot' => 1]), $all);
                $this->assertSame(
                    ['today' => 0, 'days7' => 0, 'days30' => 0],
                    DashboardStats::visitorTotals(DashboardStats::excludeBots($onlyBots), $today)
                );
            });
    }

    // Feature: travel-rental-website, Property 18: Distribution percentages sum to 100 or 0 (empty input)
    /** Validates: Requirements 12.18, 12.19 */
    public function testDistributionOfAllZeroCountsIsAllZero(): void
    {
        $this->limitTo(100)
            ->forAll(Generator\choose(1, 8))
            ->then(function (int $n): void {
                $result = DashboardStats::distribution(array_fill(0, $n, 0));
                $this->assertCount($n, $result);
                foreach ($result as $pct) {
                    $this->assertSame(0.0, $pct);
                }
            });
    }
}
