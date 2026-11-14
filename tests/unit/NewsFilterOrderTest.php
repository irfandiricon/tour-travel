<?php

use App\Libraries\ContentFilter;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class NewsFilterOrderTest extends TestCase
{
    use TestTrait;

    // Feature: travel-rental-website, Property 9: News published filtering and descending order
    /** Validates: Requirements 5.2, 5.8 */
    public function testPublishedNewsDescendingFiltersAndOrders(): void
    {
        $now = 1_800_000_000;

        $entry = Generator\tuple(
            Generator\elements(['published', 'draft', 'archived']),
            Generator\choose(-5_000_000, 5_000_000) // seconds offset from now (past and future)
        );

        $this->limitTo(100)
            ->forAll(Generator\seq($entry))
            ->then(function (array $entries) use ($now): void {
                $items = [];
                foreach ($entries as $i => [$status, $offset]) {
                    $date = date('Y-m-d H:i:s', $now + $offset); // app-local time, like stored rows
                    $items[] = $i % 2 === 0
                        ? ['id' => $i, 'status' => $status, 'publish_date' => $date]
                        : (object) ['id' => $i, 'status' => $status, 'publish_date' => $date];
                }

                $result = ContentFilter::publishedNewsDescending($items, $now);

                $expectedCount = 0;
                foreach ($entries as [$status, $offset]) {
                    if ($status === 'published' && $offset <= 0) {
                        $expectedCount++;
                    }
                }
                $this->assertCount($expectedCount, $result);

                $prev = null;
                foreach ($result as $it) {
                    $st   = is_array($it) ? $it['status'] : $it->status;
                    $date = is_array($it) ? $it['publish_date'] : $it->publish_date;
                    $ts   = strtotime($date);
                    $this->assertSame('published', $st);
                    $this->assertLessThanOrEqual($now, $ts);
                    if ($prev !== null) {
                        $this->assertGreaterThanOrEqual($ts, $prev);
                    }
                    $prev = $ts;
                }
            });
    }
}
