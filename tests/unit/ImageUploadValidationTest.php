<?php

use App\Libraries\ImageUploadHandler;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ImageUploadValidationTest extends TestCase
{
    use TestTrait;

    private const MAX = 5 * 1024 * 1024;

    private function types(): mixed
    {
        return Generators::oneOf(
            Generators::elements('image/jpeg', 'image/png', 'image/webp'),
            Generators::elements(
                'image/gif', 'image/bmp', 'image/svg+xml', 'application/pdf', 'text/plain',
                'image/JPEG', 'image/jpg', '', 'video/mp4'
            ),
            Generators::string()
        );
    }

    private function sizes(): mixed
    {
        return Generators::oneOf(
            Generators::choose(0, self::MAX),
            Generators::choose(self::MAX + 1, self::MAX * 4),
            Generators::elements(0, 1, self::MAX - 1, self::MAX, self::MAX + 1)
        );
    }

    /**
     * Validates: Requirements 6.7, 6.8, 6.9
     */
    public function testAcceptedIffAllowedTypeAndWithinSizeAndRejectionNamesConstraint(): void
    {
        // Feature: travel-rental-website, Property 14: Image upload validation
        $handler = new ImageUploadHandler();

        $this->limitTo(100)->forAll($this->types(), $this->sizes())
            ->then(function (string $type, int $size) use ($handler): void {
                $typeOk = in_array($type, ['image/jpeg', 'image/png', 'image/webp'], true);
                $sizeOk = $size <= self::MAX;

                $result = $handler->validate($type, $size);
                $codes  = array_column($result['violations'], 'code');

                self::assertSame($typeOk && $sizeOk, $result['valid']);
                self::assertSame($typeOk && $sizeOk, $handler->isAccepted($type, $size));

                self::assertSame(! $typeOk, in_array(ImageUploadHandler::ERROR_TYPE, $codes, true));
                self::assertSame(! $sizeOk, in_array(ImageUploadHandler::ERROR_SIZE, $codes, true));

                $errors = $handler->errors($type, $size);
                if ($typeOk && $sizeOk) {
                    self::assertSame([], $errors);
                } else {
                    self::assertArrayHasKey('image', $errors);
                    if (! $typeOk) {
                        self::assertStringContainsString('JPEG, PNG, and WebP', $errors['image']);
                    }
                    if (! $sizeOk) {
                        self::assertStringContainsString('5 MB', $errors['image']);
                    }
                }
            });
    }
}
