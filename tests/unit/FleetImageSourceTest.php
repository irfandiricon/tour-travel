<?php

use App\Libraries\ContentFilter;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FleetImageSourceTest extends TestCase
{
    use TestTrait;

    // Feature: travel-rental-website, Property 5: Fleet image-source selection and list totality
    /** Validates: Requirements 1.5, 1.6, 1.8 */
    public function testImageSourceSelectionAndTotality(): void
    {
        $placeholder = '/assets/img/placeholder.png';

        $path = Generator\oneOf(
            Generator\elements([null, '', '   ', 'uploads/fleet/a.jpg']),
            Generator\string()
        );

        $this->limitTo(100)
            ->forAll(Generator\seq($path))
            ->then(function (array $paths) use ($placeholder): void {
                $items = [];
                foreach ($paths as $i => $p) {
                    $items[] = $i % 2 === 0
                        ? ['id' => $i, 'image_path' => $p]
                        : (object) ['id' => $i, 'image_path' => $p];
                }

                $result = ContentFilter::fleetImageSources($items, $placeholder);

                $this->assertCount(count($items), $result);
                foreach ($paths as $i => $p) {
                    $expected = (is_string($p) && trim($p) !== '') ? $p : $placeholder;
                    $this->assertSame($expected, $result[$i]);
                }
            });
    }
}
