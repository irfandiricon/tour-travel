<?php

use CodeIgniter\Test\CIUnitTestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * @internal
 */
final class DashboardHooksPropertyTest extends CIUnitTestCase
{
    use TestTrait;

    private const OMIT = '__omit__';

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

    /** Nilai penghitung: bilangan bulat, string numerik, non-numerik, array, null, atau kunci hilang. */
    private function counter(): \Eris\Generator
    {
        return Generators::oneOf(
            Generators::choose(0, 100000),
            Generators::map(static fn (int $n): string => (string) $n, Generators::choose(0, 5000)),
            Generators::elements('abc', '', null, [1], true),
            Generators::constant(self::OMIT)
        );
    }

    /** Daftar: ukuran 0-3, atau bukan array (harus diperlakukan kosong). */
    private function listSize(): \Eris\Generator
    {
        return Generators::oneOf(Generators::choose(0, 3), Generators::elements('x', null, 'none'));
    }

    private function toInt(mixed $v): int
    {
        return is_numeric($v) ? (int) $v : 0;
    }

    private function build(array $c, string $vMode, string $bMode): array
    {
        $put = static function (array $keys, array $vals): array {
            $out = [];
            foreach ($keys as $i => $k) {
                if ($vals[$i] !== self::OMIT) {
                    $out[$k] = $vals[$i];
                }
            }

            return $out;
        };

        $visitors = $put(['total', 'unique_today', 'unique_7d', 'unique_30d', 'page_views'], array_slice($c, 0, 5));
        $bookings = $put(['total', 'last_7d', 'last_30d'], array_slice($c, 5, 3));

        return [
            'visitors' => $vMode === 'array' ? $visitors : ($vMode === 'scalar' ? 'oops' : self::OMIT),
            'bookings' => $bMode === 'array' ? $bookings : ($bMode === 'scalar' ? 'oops' : self::OMIT),
        ];
    }

    private function mkList(mixed $size, string $prefix): mixed
    {
        if (! is_int($size)) {
            return $size;
        }
        $rows = [];
        for ($i = 0; $i < $size; $i++) {
            $rows[] = ['label' => $prefix . $i, 'title' => $prefix . $i, 'publish_date' => '2025-01-0' . ($i + 1), 'count' => $i + 1];
        }

        return $rows;
    }

    /**
     * Validates: Requirements 8.1, 8.4, 8.5, 8.12, 8.13, 8.14, 8.15, 11.7
     */
    public function testDashboardMapsPartialSummaryToHooksAndEmptyStates(): void
    {
        // Feature: ui-redesign-reference, Property 11: Dashboard memetakan data ringkasan ke hook dan keadaan kosong
        $this->limitTo(100)->forAll(
            Generators::tuple(
                $this->counter(), $this->counter(), $this->counter(), $this->counter(),
                $this->counter(), $this->counter(), $this->counter(), $this->counter()
            ),
            Generators::elements('array', 'scalar', 'missing'),
            Generators::elements('array', 'scalar', 'missing'),
            Generators::tuple($this->listSize(), $this->listSize(), $this->listSize()),
            Generators::elements(null, '', 5, '2025-03-02 08:30:00'),
            Generators::elements(true, false, null),
            Generators::elements('data', 'bare', 'empty')
        )->then(function (array $c, string $vMode, string $bMode, array $sizes, mixed $login, mixed $wa, string $shape): void {
            $parts = $this->build($c, $vMode, $bMode);
            $data  = [];
            foreach ($parts as $k => $v) {
                if ($v !== self::OMIT) {
                    $data[$k] = $v;
                }
            }
            if (is_array($data['bookings'] ?? null)) {
                $tf = $this->mkList($sizes[1], 'Fleet');
                if ($tf !== self::OMIT) {
                    $data['bookings']['top_fleet'] = $tf;
                }
            }
            $data['referrers']           = $this->mkList($sizes[0], 'Ref');
            $data['latest_news']         = $this->mkList($sizes[2], 'News');
            $data['last_admin_login']    = $login;
            $data['whatsapp_configured'] = $wa;

            $summary = match ($shape) {
                'data'  => ['status' => 200, 'message' => 'OK', 'data' => $data],
                'bare'  => $data + ['visitors' => $data['visitors'] ?? []],
                default => [],
            };
            // Data efektif yang dilihat view.
            $eff = $shape === 'empty' ? [] : (isset($summary['data']) ? $data : $summary);

            $html = $this->render($summary);

            $visitors = is_array($eff['visitors'] ?? null) ? $eff['visitors'] : [];
            $bookings = is_array($eff['bookings'] ?? null) ? $eff['bookings'] : [];
            $expected = [
                $this->toInt($visitors['total'] ?? 0),
                $this->toInt($visitors['unique_today'] ?? 0),
                $this->toInt($visitors['unique_7d'] ?? 0),
                $this->toInt($visitors['unique_30d'] ?? 0),
                $this->toInt($visitors['page_views'] ?? 0),
                $this->toInt($bookings['total'] ?? 0),
                $this->toInt($bookings['last_7d'] ?? 0),
                $this->toInt($bookings['last_30d'] ?? 0),
            ];

            // Delapan kartu angka dengan nilai sesuai data (atau 0).
            $this->assertSame(8, substr_count($html, 'fs-3 fw-semibold'));
            preg_match_all('#<div class="fs-3 fw-semibold">([^<]*)</div>#', $html, $m);
            $this->assertCount(8, $m[1]);
            $this->assertSame($expected, array_map(static fn (string $s): int => (int) str_replace(',', '', $s), $m[1]));

            // Semua hook canvas dan elemen kosong.
            foreach (['chart-daily', 'chart-weekly', 'chart-language', 'chart-device'] as $id) {
                $this->assertStringContainsString('<canvas id="' . $id . '"', $html);
                $this->assertStringContainsString('id="' . $id . '-empty"', $html);
            }
            $this->assertStringContainsString('id="dashboard-data"', $html);

            // #dashboard-empty hanya bila tidak ada data pengunjung/pemesanan.
            $hasData = $expected[4] > 0 || $expected[0] > 0 || $expected[5] > 0;
            $this->assertSame($hasData, ! str_contains($html, 'id="dashboard-empty"'));

            // #whatsapp-warning hanya bila WhatsApp tidak terkonfigurasi.
            $waOk = $shape !== 'empty' && ! empty($eff['whatsapp_configured']);
            $this->assertSame(! $waOk, str_contains($html, 'id="whatsapp-warning"'));

            // Teks kosong daftar hanya bila daftar kosong.
            $isEmpty = static fn (mixed $v): bool => ! is_array($v) || $v === [];
            $topFleet = is_array($bookings['top_fleet'] ?? null) ? $bookings['top_fleet'] : [];
            $this->assertSame($isEmpty($eff['referrers'] ?? null), str_contains($html, 'No referrer data yet.'));
            $this->assertSame($isEmpty($topFleet), str_contains($html, 'No booking clicks yet.'));
            $this->assertSame($isEmpty($eff['latest_news'] ?? null), str_contains($html, 'No news yet.'));
            $login0 = $eff['last_admin_login'] ?? null;
            $this->assertSame(! (is_string($login0) && $login0 !== ''), str_contains($html, 'No login recorded.'));
        });
    }
}
