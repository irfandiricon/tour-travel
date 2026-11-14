<?php

use App\Libraries\ContentFilter;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PublishedFilterTest extends TestCase
{
    use TestTrait;

    // Feature: travel-rental-website, Property 4: Published-items filter excludes non-published
    /** Validates: Requirements 1.4, 4.8, 8.4 */
    public function testPublishedOnlyKeepsExactlyPublishedItems(): void
    {
        $status = Generator\oneOf(
            Generator\elements(['published', 'draft', 'archived', 'Published', ' published', '']),
            Generator\string()
        );

        $this->limitTo(100)
            ->forAll(Generator\seq($status))
            ->then(function (array $statuses): void {
                $items = [];
                foreach ($statuses as $i => $s) {
                    // Mix arrays and objects; id tracks original position.
                    $items[] = $i % 2 === 0
                        ? ['id' => $i, 'status' => $s]
                        : (object) ['id' => $i, 'status' => $s];
                }

                $result = ContentFilter::publishedOnly($items);

                $expectedIds = [];
                foreach ($statuses as $i => $s) {
                    if ($s === 'published') {
                        $expectedIds[] = $i;
                    }
                }

                $resultIds = array_map(
                    static fn ($it) => is_array($it) ? $it['id'] : $it->id,
                    $result
                );

                $this->assertSame($expectedIds, $resultIds);
                foreach ($result as $it) {
                    $st = is_array($it) ? $it['status'] : $it->status;
                    $this->assertSame('published', $st);
                }
            });
    }
}
