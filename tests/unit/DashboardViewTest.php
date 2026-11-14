<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Example tests for the admin dashboard view.
 *
 * Validates: Requirements 12.16, 12.24, 12.25, 12.26
 *
 * @internal
 */
final class DashboardViewTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form']);
    }

    private function render(array $summary): string
    {
        session()->set('admin_logged_in', true);

        try {
            return view('admin/dashboard', ['summary' => $summary, 'title' => 'Dashboard'], ['saveData' => false]);
        } finally {
            session()->remove('admin_logged_in');
        }
    }

    private function zeroSummary(): array
    {
        $zeroEntity = ['published' => 0, 'draft' => 0];

        return [
            'status'  => 200,
            'message' => 'OK',
            'data'    => [
                'visitors'            => ['total' => 0, 'unique_today' => 0, 'unique_7d' => 0, 'unique_30d' => 0, 'page_views' => 0],
                'bookings'            => ['total' => 0, 'last_7d' => 0, 'last_30d' => 0, 'top_fleet' => []],
                'content'             => ['fleet' => $zeroEntity, 'news' => $zeroEntity, 'faq' => $zeroEntity, 'gallery' => $zeroEntity],
                'referrers'           => [],
                'latest_news'         => [],
                'last_admin_login'    => null,
                'whatsapp_configured' => false,
            ],
        ];
    }

    private function populatedSummary(): array
    {
        $s = $this->zeroSummary();
        $s['data']['visitors']            = ['total' => 120, 'unique_today' => 5, 'unique_7d' => 40, 'unique_30d' => 90, 'page_views' => 300];
        $s['data']['bookings']            = ['total' => 12, 'last_7d' => 3, 'last_30d' => 9, 'top_fleet' => [['label' => 'Avanza Veloz', 'count' => 7]]];
        $s['data']['content']['fleet']    = ['published' => 4, 'draft' => 2];
        $s['data']['referrers']           = [['label' => 'google.com', 'count' => 33]];
        $s['data']['latest_news']         = [['title' => 'Promo Lebaran', 'publish_date' => '2025-03-01 10:00:00']];
        $s['data']['last_admin_login']    = '2025-03-02 08:30:00';
        $s['data']['whatsapp_configured'] = true;

        return $s;
    }

    public function testAllZeroSummaryShowsEmptyStatesAndWarning(): void
    {
        $html = $this->render($this->zeroSummary());

        $this->assertStringContainsString('id="dashboard-empty"', $html);
        $this->assertStringContainsString('No referrer data yet.', $html);
        $this->assertStringContainsString('No booking clicks yet.', $html);
        $this->assertStringContainsString('No news yet.', $html);
        $this->assertStringContainsString('No login recorded.', $html);
        $this->assertStringContainsString('id="whatsapp-warning"', $html);
        $this->assertStringContainsString('admin/site-config', $html);
        $this->assertStringContainsString('chart-daily-empty', $html);
    }

    public function testPopulatedSummaryRendersValuesAndHidesWarning(): void
    {
        $html = $this->render($this->populatedSummary());

        $this->assertStringNotContainsString('id="dashboard-empty"', $html);
        $this->assertStringNotContainsString('id="whatsapp-warning"', $html);
        $this->assertStringNotContainsString('No referrer data yet.', $html);
        $this->assertStringNotContainsString('No booking clicks yet.', $html);
        $this->assertStringNotContainsString('No news yet.', $html);
        $this->assertStringContainsString('google.com', $html);
        $this->assertStringContainsString('Avanza Veloz', $html);
        $this->assertStringContainsString('Promo Lebaran', $html);
        $this->assertStringContainsString('2025-03-02 08:30:00', $html);
        $this->assertStringContainsString('Published 4', $html);
        $this->assertStringContainsString('Draft 2', $html);
    }

    /**
     * @dataProvider summaryProvider
     */
    public function testCardsAndCanvasesAlwaysRendered(string $which): void
    {
        $html = $this->render($which === 'zero' ? $this->zeroSummary() : $this->populatedSummary());

        // 8 visitor/booking cards + 4 content cards
        $this->assertSame(8, substr_count($html, 'fs-3 fw-semibold'));
        $this->assertSame(4, substr_count($html, 'badge text-bg-success'));
        foreach (['Total visitors', 'Unique today', 'Unique last 7 days', 'Unique last 30 days', 'Page views', 'Booking clicks'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        foreach (['chart-daily', 'chart-language', 'chart-device'] as $id) {
            $this->assertStringContainsString('<canvas id="' . $id . '"', $html);
        }
        $this->assertStringContainsString('id="dashboard-data"', $html);
        $this->assertStringContainsString('<canvas id="chart-weekly"', $html);
        $this->assertStringContainsString('chart-weekly-empty', $html);
        $this->assertStringContainsString('donut-legend', $html);
        $this->assertStringContainsString('aria-label="Open Fleet"', $html);
    }

    public static function summaryProvider(): array
    {
        return ['zero' => ['zero'], 'populated' => ['populated']];
    }

    public function testOnlyLocalChartJsIsReferenced(): void
    {
        foreach ([$this->zeroSummary(), $this->populatedSummary()] as $summary) {
            $html = $this->render($summary);

            $this->assertStringContainsString('/assets/vendor/chartjs/chart.umd.min.js', $html);
            $this->assertSame(1, substr_count($html, 'chart.umd.min.js'));
            $this->assertSame(0, preg_match('#(jsdelivr|unpkg|cdnjs|cdn\.)#i', $html));
            preg_match_all('#<script[^>]+src="([^"]*)"#i', $html, $m);
            foreach ($m[1] as $src) {
                if (stripos($src, 'chart') !== false) {
                    $this->assertStringContainsString('/assets/vendor/chartjs/', $src);
                }
            }
        }
    }
}
