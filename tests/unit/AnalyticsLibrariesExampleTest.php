<?php

use App\Libraries\ContentValidator;
use App\Libraries\DashboardStats;
use App\Libraries\DeviceDetector;
use App\Libraries\VisitorTracker;
use PHPUnit\Framework\TestCase;

/**
 * Example tests for the analytics libraries and validators.
 *
 * Validates: Requirements 11.18, 12.5, 12.7, 12.8, 12.11, 12.16, 12.20, 12.21
 *
 * @internal
 */
final class AnalyticsLibrariesExampleTest extends TestCase
{
    /**
     * @return array<string,array{0:?string,1:string}>
     */
    public static function userAgentProvider(): array
    {
        return [
            'chrome desktop' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'desktop'],
            'iphone'         => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'mobile'],
            'ipad'           => ['Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'tablet'],
            'android phone'  => ['Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'mobile'],
            'googlebot'      => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'bot'],
            'curl'           => ['curl/8.4.0', 'bot'],
            'empty'          => ['', 'bot'],
            'null'           => [null, 'bot'],
        ];
    }

    /**
     * @dataProvider userAgentProvider
     */
    public function testDeviceDetectorSamples(?string $ua, string $expected): void
    {
        $this->assertSame($expected, DeviceDetector::classify($ua));
        $this->assertSame($expected === 'bot', DeviceDetector::isBot($ua));
    }

    /**
     * @return array<string,array{0:string,1:bool}>
     */
    public static function pathProvider(): array
    {
        return [
            'root'            => ['/', true],
            'empty'           => ['', true],
            'fleet page'      => ['/fleet', true],
            'language page'   => ['/id/tours', true],
            'admin'           => ['/admin/dashboard', false],
            'api'             => ['/api/v1/dashboard/summary', false],
            'signin'          => ['/signin', false],
            'dashboard'       => ['/dashboard', false],
            'track'           => ['/track/booking', false],
            'assets'          => ['/assets/css/site.css', false],
            'uploads'         => ['/uploads/a.jpg', false],
            'robots'          => ['/robots.txt', false],
            'sitemap'         => ['/sitemap.xml', false],
            'favicon'         => ['/favicon.ico', false],
            'asset extension' => ['/foo/bar.png', false],
            'query string'    => ['/fleet?x=1', true],
            'upper admin'     => ['/ADMIN/x', false],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testShouldTrackPathTable(string $path, bool $expected): void
    {
        $this->assertSame($expected, VisitorTracker::shouldTrack($path));
    }

    public function testReferrerHostParsing(): void
    {
        $this->assertNull(VisitorTracker::referrerHost(null, 'site.test'));
        $this->assertNull(VisitorTracker::referrerHost('   ', 'site.test'));
        $this->assertNull(VisitorTracker::referrerHost('not a url', 'site.test'));
        $this->assertNull(VisitorTracker::referrerHost('https://SITE.test/page', 'site.test'));
        $this->assertSame('www.google.com', VisitorTracker::referrerHost('https://www.Google.com/search?q=a', 'site.test'));
    }

    public function testRecordSwallowsModelFailure(): void
    {
        $model = new class () {
            public function insert(array $data): void
            {
                throw new RuntimeException('db down');
            }
        };

        $tracker = new VisitorTracker($model);
        $this->assertFalse($tracker->record('/fleet', '203.0.113.9', 'Mozilla/5.0 Chrome/120', 'en', null, 'site.test'));
    }

    public function testRecordInsertsAndSkipsExcludedPaths(): void
    {
        $model = new class () {
            /** @var list<array<string,mixed>> */
            public array $rows = [];

            public function insert(array $data): void
            {
                $this->rows[] = $data;
            }
        };

        $tracker = new VisitorTracker($model);
        $this->assertTrue($tracker->record('/fleet', '203.0.113.9', 'curl/8.0', 'en', 'https://google.com/', 'site.test', '2025-03-04 10:00:00'));
        $this->assertFalse($tracker->record('/admin/x', '203.0.113.9', 'Chrome', 'en', null, 'site.test'));
        $this->assertCount(1, $model->rows);
        $this->assertSame(1, $model->rows[0]['is_bot']);
        $this->assertSame('google.com', $model->rows[0]['referrer_host']);
        $this->assertSame('2025-03-04', $model->rows[0]['visit_date']);
        $this->assertStringNotContainsString('203.0.113.9', json_encode($model->rows[0]));
    }

    public function testValidateBookingClickBoundaries(): void
    {
        $ok = ['fleet_id' => 1, 'fleet_name' => str_repeat('a', 200), 'lang' => 'ms'];
        $this->assertSame([], ContentValidator::validateBookingClick($ok));
        $this->assertSame([], ContentValidator::validateBookingClick(['fleet_id' => '12'] + $ok));

        $this->assertArrayHasKey('fleet_id', ContentValidator::validateBookingClick(['fleet_id' => 0] + $ok));
        $this->assertArrayHasKey('fleet_id', ContentValidator::validateBookingClick(['fleet_id' => '-1'] + $ok));
        $this->assertArrayHasKey('fleet_id', ContentValidator::validateBookingClick(['fleet_id' => 'abc'] + $ok));
        $this->assertArrayHasKey('fleet_id', ContentValidator::validateBookingClick(['fleet_name' => 'x', 'lang' => 'en']));
        $this->assertArrayHasKey('fleet_name', ContentValidator::validateBookingClick(['fleet_name' => ''] + $ok));
        $this->assertArrayHasKey('fleet_name', ContentValidator::validateBookingClick(['fleet_name' => str_repeat('a', 201)] + $ok));
        $this->assertArrayHasKey('lang', ContentValidator::validateBookingClick(['lang' => 'fr'] + $ok));
    }

    public function testValidateSeoSettingsBoundaries(): void
    {
        $this->assertSame([], ContentValidator::validateSeoSettings([]));
        $this->assertSame([], ContentValidator::validateSeoSettings([
            'site_name_en'        => str_repeat('a', 150),
            'meta_description_en' => str_repeat('a', 320),
            'meta_keywords_en'    => str_repeat('a', 255),
            'og_image'            => str_repeat('a', 500),
        ]));

        $errors = ContentValidator::validateSeoSettings([
            'site_name_en'        => str_repeat('a', 151),
            'meta_description_en' => str_repeat('a', 321),
            'meta_keywords_en'    => str_repeat('a', 256),
            'og_image'            => str_repeat('a', 501),
        ]);
        $this->assertSame(['site_name_en', 'meta_description_en', 'meta_keywords_en', 'og_image'], array_keys($errors));

        // A non-English value without the English one is rejected.
        $errors = ContentValidator::validateSeoSettings(['site_name_id' => 'Nama']);
        $this->assertArrayHasKey('site_name_en', $errors);
    }

    public function testTopNOrderingAndTies(): void
    {
        $result = DashboardStats::topN(['b' => 5, 'a' => 5, 'c' => 9, 'z' => 0, 'd' => 1], 3);
        $this->assertSame([
            ['label' => 'c', 'count' => 9],
            ['label' => 'a', 'count' => 5],
            ['label' => 'b', 'count' => 5],
        ], $result);

        $this->assertSame([], DashboardStats::topN(['a' => 1], 0));
        $this->assertSame([], DashboardStats::topN([], 5));
    }

    public function testVisitorTotalsForFixedRows(): void
    {
        $rows = [
            ['visit_date' => '2025-03-10', 'visitor_hash' => 'h1'],
            ['visit_date' => '2025-03-10', 'visitor_hash' => 'h1'], // duplicate pair
            ['visit_date' => '2025-03-10', 'visitor_hash' => 'h2'],
            ['visit_date' => '2025-03-04', 'visitor_hash' => 'h1'], // day 7 of window
            ['visit_date' => '2025-03-03', 'visitor_hash' => 'h1'], // outside 7, inside 30
            ['visit_date' => '2025-02-09', 'visitor_hash' => 'h3'], // day 30 of window
            ['visit_date' => '2025-02-08', 'visitor_hash' => 'h4'], // outside 30
            ['visit_date' => '2025-03-11', 'visitor_hash' => 'h5'], // future
        ];

        $this->assertSame(
            ['today' => 2, 'days7' => 3, 'days30' => 5],
            DashboardStats::visitorTotals($rows, '2025-03-10')
        );
        $this->assertSame(
            ['today' => 0, 'days7' => 0, 'days30' => 0],
            DashboardStats::visitorTotals([], '2025-03-10')
        );
    }
}
