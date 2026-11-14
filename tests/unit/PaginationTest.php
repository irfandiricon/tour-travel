<?php

use App\Libraries\Paginator;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PaginationTest extends TestCase
{
    use TestTrait;

    // Feature: travel-rental-website, Property 8: Pagination metadata consistency
    /** Validates: Requirements 4.2, 5.2 */
    public function testPaginationMetadataIsConsistent(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\choose(0, 250),
                Generator\choose(-5, 60),
                Generator\oneOf(Generator\choose(-5, 150), Generator\constant(null))
            )
            ->then(function (int $size, int $page, $perPage): void {
                $items = range(1000, 1000 + $size - 1);
                if ($size === 0) {
                    $items = [];
                }

                $result = Paginator::paginate($items, $page, $perPage);

                $expectedPer  = $perPage === null || $perPage < 1 ? 10 : min($perPage, 100);
                $expectedPage = max(1, $page);

                $this->assertLessThanOrEqual($expectedPer, count($result['data']));
                $this->assertSame($size, $result['total']);
                $this->assertSame($expectedPage, $result['page']);
                $this->assertSame($expectedPer, $result['per_page']);
                $this->assertSame(
                    array_slice($items, ($expectedPage - 1) * $expectedPer, $expectedPer),
                    $result['data']
                );
            });
    }
}
