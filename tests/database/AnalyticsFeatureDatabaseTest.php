<?php

use App\Database\Migrations\AddSeoColumnsToSiteConfig;
use App\Database\Migrations\CreateSiteEvents;
use App\Database\Migrations\CreateVisitorLogs;
use App\Filters\InternalApiFilter;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Feature and database tests against the local MySQL `tour_travel` database
 * (connection comes from .env, group `default`). Every data-changing test runs
 * inside a transaction that is rolled back; the migrations are idempotent, so
 * applying them twice leaves the schema as it was. No server is started.
 *
 * @internal
 */
final class AnalyticsFeatureDatabaseTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $conn;
    private bool $inTransaction = false;
    private array $originalTests = [];
    private string $originalGroup = 'default';
    private string $originalRemoteAddr = '';

    protected function setUp(): void
    {
        parent::setUp();

        // ENVIRONMENT=testing forces the SQLite `tests` group; point it at the real
        // dev database for this class only (restored in tearDown).
        $config                 = config('Database');
        $this->originalTests    = $config->tests;
        $this->originalGroup    = $config->defaultGroup;
        $config->tests          = $config->default;
        $config->defaultGroup   = 'default';

        self::clearConnections(); // drop connections cached by earlier tests

        try {
            $this->conn = Database::connect();
            $this->conn->initialize();
        } catch (Throwable $e) {
            $this->markTestSkipped('MySQL tour_travel is not reachable: ' . $e->getMessage());
        }

        foreach (['visitor_logs', 'site_events'] as $table) {
            if (! $this->conn->tableExists($table)) {
                $this->markTestSkipped("Table {$table} missing; run php spark migrate.");
            }
        }

        $this->originalRemoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        cache()->deleteMatching('track_booking_*');
    }

    protected function tearDown(): void
    {
        if (isset($this->conn) && $this->inTransaction) {
            $this->conn->transRollback();
            $this->conn->transComplete();
            $this->inTransaction = false;
        }

        if (function_exists('cache')) {
            cache()->deleteMatching('track_booking_*');
        }

        if ($this->originalRemoteAddr === '') {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddr;
        }

        $config               = config('Database');
        $config->tests        = $this->originalTests;
        $config->defaultGroup = $this->originalGroup;
        self::clearConnections();
        parent::tearDown();
    }

    /** Forget shared connections so the next connect() uses the current config. */
    private static function clearConnections(): void
    {
        $prop = new ReflectionProperty(\CodeIgniter\Database\Config::class, 'instances');
        $prop->setValue(null, []);
    }

    private function begin(): void
    {
        $this->conn->transException(false)->transStart();
        $this->inTransaction = true;
    }

    private function countEvents(): int
    {
        return (int) $this->conn->table('site_events')->where('event_type', 'booking_click')->countAllResults();
    }

    /** @return array<string, string> */
    private function browserHeaders(): array
    {
        return ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0 Safari/537.36'];
    }

    private function bookingParams(array $override = []): array
    {
        return $override + ['fleet_id' => '7', 'fleet_name' => 'Toyota Alphard', 'lang' => 'en'];
    }

    private function postBooking(array $params, ?array $headers = null)
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.' . random_int(1, 250);
        $req = $this->withHeaders($headers ?? $this->browserHeaders());

        return $req->call('post', 'track/booking', $params);
    }

    // ------------------------------------------------------------ migrations

    public function testMigrationsApplyTwiceAndLeaveExpectedSchema(): void
    {
        foreach (glob(APPPATH . 'Database/Migrations/2025-02-01-*.php') as $file) {
            require_once $file;
        }

        $forge = Database::forge(null);

        for ($i = 0; $i < 2; $i++) {
            (new AddSeoColumnsToSiteConfig($forge))->up();
            (new CreateVisitorLogs($forge))->up();
            (new CreateSiteEvents($forge))->up();
        }

        $seo = ['og_image'];
        foreach (['en', 'id', 'ms'] as $lang) {
            $seo[] = "site_name_{$lang}";
            $seo[] = "meta_description_{$lang}";
            $seo[] = "meta_keywords_{$lang}";
        }
        $siteConfigFields = $this->conn->getFieldNames('site_config');
        foreach ($seo as $field) {
            $this->assertContains($field, $siteConfigFields, "site_config.{$field}");
        }
        $this->assertCount(count($siteConfigFields), array_unique($siteConfigFields));

        $visitorFields = $this->conn->getFieldNames('visitor_logs');
        foreach (['id', 'visit_date', 'visitor_hash', 'page', 'lang', 'device_type', 'referrer_host', 'is_bot', 'created_at'] as $field) {
            $this->assertContains($field, $visitorFields, "visitor_logs.{$field}");
        }

        $eventFields = $this->conn->getFieldNames('site_events');
        foreach (['id', 'event_type', 'ref_id', 'ref_label', 'lang', 'visitor_hash', 'created_at'] as $field) {
            $this->assertContains($field, $eventFields, "site_events.{$field}");
        }

        $this->assertIndexColumns('visitor_logs', ['visit_date']);
        $this->assertIndexColumns('visitor_logs', ['visitor_hash']);
        $this->assertIndexColumns('site_events', ['event_type', 'created_at']);
        $this->assertIndexColumns('site_events', ['ref_id']);
    }

    /** @param list<string> $columns */
    private function assertIndexColumns(string $table, array $columns): void
    {
        foreach ($this->conn->getIndexData($table) as $index) {
            if (array_values($index->fields) === $columns) {
                $this->assertTrue(true);

                return;
            }
        }
        $this->fail("No index on {$table}(" . implode(', ', $columns) . ')');
    }

    // ------------------------------------------------------------ crawlers

    public function testRobotsTxt(): void
    {
        $result = $this->get('robots.txt');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type');
        $this->assertStringContainsString('text/plain', $result->response()->getHeaderLine('Content-Type'));
        $body = $result->getBody();
        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertMatchesRegularExpression('/^Sitemap: https?:\/\/\S+\/sitemap\.xml$/m', $body);
    }

    public function testSitemapXmlIsWellFormedWithAlternates(): void
    {
        $result = $this->get('sitemap.xml');

        $result->assertStatus(200);
        $this->assertStringContainsString('application/xml', $result->response()->getHeaderLine('Content-Type'));

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML((string) $result->response()->getBody()), 'sitemap.xml must be well-formed XML');

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xpath->registerNamespace('x', 'http://www.w3.org/1999/xhtml');
        // Landing page + the Fleet, Gallery, News & Information pages.
        $locs = [];
        foreach ($xpath->query('//s:url/s:loc') as $node) {
            $locs[] = $node->textContent;
        }
        $this->assertCount(4, $locs);
        foreach (['fleet', 'gallery', 'news'] as $slug) {
            $this->assertCount(1, array_filter($locs, static fn (string $loc): bool => str_ends_with($loc, '/' . $slug)), $slug);
        }

        // Every URL lists the same four alternates.
        foreach ($xpath->query('//s:url') as $url) {
            $langs = [];
            foreach ($xpath->query('x:link[@rel="alternate"]', $url) as $node) {
                $langs[] = $node->getAttribute('hreflang');
            }
            sort($langs);
            $this->assertSame(['en', 'id', 'ms', 'x-default'], $langs);
        }
    }

    // ------------------------------------------------------- booking beacon

    public function testBookingClickValidPayloadInsertsEvent(): void
    {
        $this->begin();
        $before = $this->countEvents();

        $result = $this->postBooking($this->bookingParams());

        $result->assertStatus(200);
        $this->assertSame($before + 1, $this->countEvents());

        $row = $this->conn->table('site_events')->where('event_type', 'booking_click')->orderBy('id', 'DESC')->get(1)->getRowArray();
        $this->assertSame('7', (string) $row['ref_id']);
        $this->assertSame('Toyota Alphard', $row['ref_label']);
        $this->assertSame('en', $row['lang']);
        $this->assertSame(64, strlen($row['visitor_hash']));
    }

    public function testBookingClickInvalidPayloadReturns422WithoutInsert(): void
    {
        $this->begin();
        $before = $this->countEvents();

        $result = $this->postBooking($this->bookingParams(['fleet_id' => 'abc', 'fleet_name' => '', 'lang' => 'xx']));

        $result->assertStatus(422);
        $this->assertSame($before, $this->countEvents());
    }

    public function testBookingClickEleventhRequestIsRateLimited(): void
    {
        $this->begin();
        $before = $this->countEvents();
        $_SERVER['REMOTE_ADDR'] = '203.0.113.77';

        for ($i = 1; $i <= 10; $i++) {
            $this->withHeaders($this->browserHeaders())
                ->call('post', 'track/booking', $this->bookingParams())
                ->assertStatus(200);
        }

        $result = $this->withHeaders($this->browserHeaders())->call('post', 'track/booking', $this->bookingParams());

        $result->assertStatus(429);
        $this->assertNotSame('', $result->response()->getHeaderLine('Retry-After'));
        $this->assertSame($before + 10, $this->countEvents());
    }

    public function testBookingClickFromBotIsNotRecorded(): void
    {
        $this->begin();
        $before = $this->countEvents();

        $result = $this->postBooking(
            $this->bookingParams(),
            ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']
        );

        $result->assertStatus(204);
        $this->assertSame($before, $this->countEvents());
    }

    // ------------------------------------------------- dashboard summary API

    public function testDashboardSummaryRequiresSessionOrSignature(): void
    {
        $result = $this->call('get', 'api/v1/dashboard/summary');

        $result->assertStatus(401);
    }

    public function testDashboardSummaryRejectsInvalidSignature(): void
    {
        $result = $this->withHeaders([
            InternalApiFilter::PROXY_HEADER     => '1',
            InternalApiFilter::SIGNATURE_HEADER => 'not-a-valid-signature',
        ])->call('get', 'api/v1/dashboard/summary');

        $result->assertStatus(401);
    }

    public function testDashboardSummaryWithInternalProxySignature(): void
    {
        $secret = trim((string) env('INTERNAL_PROXY_SECRET', ''));
        if ($secret === '') {
            $secret = 'test-secret-' . bin2hex(random_bytes(4));
            $_SERVER['INTERNAL_PROXY_SECRET'] = $secret;
        }

        try {
            $result = $this->withHeaders([
                InternalApiFilter::PROXY_HEADER     => '1',
                InternalApiFilter::SIGNATURE_HEADER => hash_hmac('sha256', InternalApiFilter::SIGNATURE_PAYLOAD, $secret),
            ])->call('get', 'api/v1/dashboard/summary');

            $result->assertStatus(200);
            $json = json_decode($result->getJSON(), true);
            $this->assertIsArray($json);
            $this->assertArrayHasKey('data', $json);
            $this->assertIsArray($json['data']);
        } finally {
            unset($_SERVER['INTERNAL_PROXY_SECRET']);
        }
    }
}
