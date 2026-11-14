<?php

use App\Libraries\VisitorHasher;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class VisitorHasherPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * IPv4 generator "a.b.c.d" (always at least 7 characters).
     */
    private function ips(): mixed
    {
        return Generators::map(
            static fn (array $o): string => implode('.', $o),
            Generators::tuple(
                Generators::choose(0, 255),
                Generators::choose(0, 255),
                Generators::choose(0, 255),
                Generators::choose(0, 255)
            )
        );
    }

    /**
     * Date generator in Y-m-d form.
     */
    private function dates(): mixed
    {
        return Generators::map(
            static fn (int $d): string => gmdate('Y-m-d', $d * 86400),
            Generators::choose(10000, 30000)
        );
    }

    /**
     * Validates: Requirements 12.2, 12.3, 12.4
     */
    public function testHashIsPrivateStableAndDailyRotating(): void
    {
        // Feature: travel-rental-website, Property 19: Visitor hash privacy and stability
        $this->limitTo(100)->forAll(
            $this->ips(),
            Generators::string(),
            $this->dates(),
            $this->dates(),
            Generators::string()
        )->then(static function (string $ip, string $ua, string $dateA, string $dateB, string $salt): void {
            self::assertGreaterThanOrEqual(7, strlen($ip));

            $hash = VisitorHasher::hash($ip, $ua, $dateA, $salt);

            // 64-char lowercase hex, raw IP never present.
            self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/D', $hash);
            self::assertStringNotContainsString($ip, $hash);

            // Stable for identical inputs.
            self::assertSame($hash, VisitorHasher::hash($ip, $ua, $dateA, $salt));

            // Differs across different dates.
            if ($dateA !== $dateB) {
                self::assertNotSame($hash, VisitorHasher::hash($ip, $ua, $dateB, $salt));
            }
        });
    }
}
