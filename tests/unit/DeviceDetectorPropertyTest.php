<?php

use App\Libraries\DeviceDetector;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DeviceDetectorPropertyTest extends TestCase
{
    use TestTrait;

    private const CLASSES = ['desktop', 'mobile', 'tablet', 'bot'];

    /**
     * Validates: Requirements 12.5
     */
    public function testClassifyIsTotalAndIsBotIffBot(): void
    {
        // Feature: travel-rental-website, Property 20: Device classification totality
        $this->limitTo(100)->forAll(
            Generators::oneOf(
                Generators::string(),
                Generators::elements([
                    '',
                    ' ',
                    "\0\xff\xfe",
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0 Safari/537.36',
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148',
                    'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)',
                    'Mozilla/5.0 (compatible; Googlebot/2.1)',
                    'curl/8.0.1',
                ])
            )
        )->then(static function (string $ua): void {
            $class = DeviceDetector::classify($ua);

            self::assertContains($class, self::CLASSES);
            self::assertSame($class === DeviceDetector::BOT, DeviceDetector::isBot($ua));
        });
    }

    public function testNullIsClassifiedAsBot(): void
    {
        self::assertSame(DeviceDetector::BOT, DeviceDetector::classify(null));
        self::assertTrue(DeviceDetector::isBot(null));
    }
}
