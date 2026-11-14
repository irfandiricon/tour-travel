<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DashboardJsonRoundTripPropertyTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form', 'ui']);
    }

    /**
     * Teks acak: unicode, kutip, tanda markup, dan ampersand.
     */
    private function texts(): \Eris\Generator
    {
        $fragments = Generators::elements(
            'google.com', 'Avanza Veloz', 'Promo Lebaran', 'Ã©tÃ©', 'æ—¥æœ¬èªž', 'Ù…Ø±Ø­Ø¨Ø§', 'ðŸš', 'a', ' ',
            '<script>alert(1)</script>', '</script>', '<', '>', '"', "'", '&', '&amp;', '\\', '/'
        );

        return Generators::map(
            static fn (array $parts): string => implode('', $parts),
            Generators::seq($fragments)
        );
    }

    private function render(array $data): string
    {
        session()->set('admin_logged_in', true);

        try {
            return view(
                'admin/dashboard',
                ['summary' => ['status' => 200, 'message' => 'OK', 'data' => $data], 'title' => 'Dashboard'],
                ['saveData' => false]
            );
        } finally {
            session()->remove('admin_logged_in');
        }
    }

    /**
     * Feature: ui-redesign-reference, Property 12: JSON dashboard bersifat round trip
     *
     * **Validates: Requirements 8.17**
     */
    public function testDashboardJsonRoundTrip(): void
    {
        // Feature: ui-redesign-reference, Property 12: JSON dashboard bersifat round trip
        $this->limitTo(100)->forAll(
            $this->texts(),
            $this->texts(),
            $this->texts(),
            Generators::choose(0, 100000),
            Generators::bool()
        )->then(function (string $a, string $b, string $c, int $n, bool $wa): void {
            $data = [
                'visitors'            => ['total' => $n, 'unique_today' => $n % 7, 'unique_7d' => $n % 13, 'unique_30d' => $n % 31, 'page_views' => $n * 2],
                'bookings'            => ['total' => $n, 'last_7d' => 1, 'last_30d' => 2, 'top_fleet' => [['label' => $a, 'count' => $n]]],
                'content'             => ['fleet' => ['published' => 1, 'draft' => 0]],
                'referrers'           => [['label' => $b, 'count' => $n]],
                'latest_news'         => [['title' => $c, 'publish_date' => '2025-03-01 10:00:00']],
                'last_admin_login'    => $a . $c,
                'whatsapp_configured' => $wa,
            ];

            $html = $this->render($data);

            $this->assertSame(
                1,
                preg_match('#<script type="application/json" id="dashboard-data">(.*?)</script>#s', $html, $m),
                'dashboard-data script block must exist exactly once and not be terminated early'
            );

            $decoded = json_decode($m[1], true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'embedded JSON must be valid');
            $this->assertSame(['data' => $data], $decoded);
        });
    }
}
