<?php

namespace App\Controllers\ApiV1;

use App\Libraries\DashboardStats;
use App\Models\AdminUserModel;
use App\Models\FaqModel;
use App\Models\FleetModel;
use App\Models\GalleryModel;
use App\Models\NewsModel;
use App\Models\SiteConfigModel;
use App\Models\SiteEventModel;
use App\Models\VisitorLogModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * api/v1 dashboard tier: read-only aggregates for the admin dashboard.
 * Response shape: {status, message, data, total}. Bots (is_bot = 1) are excluded
 * from every visitor aggregate in SQL.
 */
class DashboardController extends ResourceController
{
    protected $format = 'json';

    private const LANGS   = ['en', 'id', 'ms'];
    private const DEVICES = ['desktop', 'mobile', 'tablet'];
    private const DAYS    = 30;

    /**
     * GET dashboard/summary
     */
    public function summary(): ResponseInterface
    {
        $db = db_connect();

        $missing = [];
        foreach (['visitor_logs', 'site_events'] as $table) {
            if (! $db->tableExists($table)) {
                $missing[] = $table;
            }
        }

        if ($missing !== []) {
            $data = self::emptySummary();
            $this->fillCommon($data);

            return $this->respond200(
                'Analytics tables missing (' . implode(', ', $missing) . '). Run "php spark migrate" to create them.',
                $data
            );
        }

        try {
            $data = self::emptySummary();
            $this->fillVisitors($data);
            $this->fillBookings($data);
            $this->fillCommon($data);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard summary failed: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 500,
                'message' => 'Failed to build dashboard summary',
                'data'    => self::emptySummary(),
                'total'   => 0,
            ]);
        }

        return $this->respond200('OK', $data);
    }

    /**
     * Zero-shaped summary matching the documented `data` structure.
     *
     * @return array<string,mixed>
     */
    public static function emptySummary(): array
    {
        $dist = static function (array $keys): array {
            $out = [];
            foreach ($keys as $k) {
                $out[$k] = ['count' => 0, 'percent' => 0.0];
            }

            return $out;
        };

        $daily = [];
        $end   = date('Y-m-d');
        foreach (DashboardStats::fillDateRange([], $end, self::DAYS) as $d) {
            $daily[] = ['date' => $d['date'], 'page_views' => 0, 'unique' => 0];
        }

        $entity = ['published' => 0, 'draft' => 0];

        return [
            'visitors'  => ['total' => 0, 'unique_today' => 0, 'unique_7d' => 0, 'unique_30d' => 0, 'page_views' => 0],
            'daily'     => $daily,
            'languages' => $dist(self::LANGS),
            'devices'   => $dist(self::DEVICES),
            'referrers' => [],
            'bookings'  => ['total' => 0, 'last_7d' => 0, 'last_30d' => 0, 'top_fleet' => []],
            'content'   => [
                'fleet'       => $entity,
                'news'        => $entity,
                'faq'         => $entity,
                'gallery'     => $entity,
            ],
            'latest_news'         => [],
            'last_admin_login'    => null,
            'whatsapp_configured' => false,
        ];
    }

    // ------------------------------------------------------------------

    private function fillVisitors(array &$data): void
    {
        $today = date('Y-m-d');
        $from  = date('Y-m-d', strtotime($today . ' -' . (self::DAYS - 1) . ' days'));

        $log = new VisitorLogModel();

        // All-time totals (non-bot).
        $row = $log->builder()
            ->select('COUNT(*) AS page_views, COUNT(DISTINCT visit_date, visitor_hash) AS visitors', false)
            ->where('is_bot', 0)
            ->get()->getRowArray();
        $data['visitors']['page_views'] = (int) ($row['page_views'] ?? 0);
        $data['visitors']['total']      = (int) ($row['visitors'] ?? 0);

        // Distinct (date, hash) pairs for the trailing window feed DashboardStats.
        $pairs = $log->builder()
            ->select('visit_date, visitor_hash')
            ->distinct()
            ->where('is_bot', 0)
            ->where('visit_date >=', $from)
            ->where('visit_date <=', $today)
            ->get()->getResultArray();
        $totals = DashboardStats::visitorTotals($pairs, $today);
        $data['visitors']['unique_today'] = $totals['today'];
        $data['visitors']['unique_7d']    = $totals['days7'];
        $data['visitors']['unique_30d']   = $totals['days30'];

        // Daily page views and uniques.
        $dailyRows = $log->builder()
            ->select('visit_date, COUNT(*) AS page_views, COUNT(DISTINCT visitor_hash) AS uniq', false)
            ->where('is_bot', 0)
            ->where('visit_date >=', $from)
            ->where('visit_date <=', $today)
            ->groupBy('visit_date')
            ->get()->getResultArray();
        $views = [];
        $uniq  = [];
        foreach ($dailyRows as $r) {
            $views[$r['visit_date']] = (int) $r['page_views'];
            $uniq[$r['visit_date']]  = (int) $r['uniq'];
        }
        $viewSeries = DashboardStats::fillDateRange($views, $today, self::DAYS);
        $uniqSeries = DashboardStats::fillDateRange($uniq, $today, self::DAYS);
        $daily = [];
        foreach ($viewSeries as $i => $v) {
            $daily[] = ['date' => $v['date'], 'page_views' => $v['count'], 'unique' => $uniqSeries[$i]['count']];
        }
        $data['daily'] = $daily;

        // Language and device distribution over the trailing window (non-bot page views).
        $data['languages'] = $this->distributionBy($log, 'lang', self::LANGS, $from, $today);
        $data['devices']   = $this->distributionBy($log, 'device_type', self::DEVICES, $from, $today);

        // Top referrers over the trailing window.
        $refRows = $log->builder()
            ->select('referrer_host, COUNT(*) AS c', false)
            ->where('is_bot', 0)
            ->where('referrer_host IS NOT NULL', null, false)
            ->where('referrer_host !=', '')
            ->where('visit_date >=', $from)
            ->where('visit_date <=', $today)
            ->groupBy('referrer_host')
            ->get()->getResultArray();
        $refs = [];
        foreach ($refRows as $r) {
            $refs[$r['referrer_host']] = (int) $r['c'];
        }
        $data['referrers'] = DashboardStats::topN($refs, 5);
    }

    /**
     * @param list<string> $keys
     * @return array<string,array{count:int,percent:float}>
     */
    private function distributionBy(VisitorLogModel $log, string $column, array $keys, string $from, string $to): array
    {
        $rows = $log->builder()
            ->select("{$column} AS k, COUNT(*) AS c", false)
            ->where('is_bot', 0)
            ->where('visit_date >=', $from)
            ->where('visit_date <=', $to)
            ->groupBy($column)
            ->get()->getResultArray();

        $counts = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            if (array_key_exists($r['k'], $counts)) {
                $counts[$r['k']] = (int) $r['c'];
            }
        }

        $percent = DashboardStats::distribution($counts);
        $out     = [];
        foreach ($keys as $k) {
            $out[$k] = ['count' => $counts[$k], 'percent' => (float) $percent[$k]];
        }

        return $out;
    }

    private function fillBookings(array &$data): void
    {
        $events = new SiteEventModel();
        $now    = date('Y-m-d H:i:s');
        $d7     = date('Y-m-d H:i:s', strtotime('-7 days'));
        $d30    = date('Y-m-d H:i:s', strtotime('-30 days'));

        $count = static function (?string $since) use ($events, $now): int {
            $b = $events->builder()->where('event_type', 'booking_click');
            if ($since !== null) {
                $b->where('created_at >=', $since)->where('created_at <=', $now);
            }

            return (int) $b->countAllResults();
        };

        $data['bookings']['total']    = $count(null);
        $data['bookings']['last_7d']  = $count($d7);
        $data['bookings']['last_30d'] = $count($d30);

        $rows = $events->builder()
            ->select('ref_label, COUNT(*) AS c', false)
            ->where('event_type', 'booking_click')
            ->where('ref_label IS NOT NULL', null, false)
            ->where('ref_label !=', '')
            ->groupBy('ref_label')
            ->get()->getResultArray();
        $labels = [];
        foreach ($rows as $r) {
            $labels[$r['ref_label']] = (int) $r['c'];
        }
        $data['bookings']['top_fleet'] = DashboardStats::topN($labels, 5);
    }

    /**
     * Content counts, latest news, last admin login and WhatsApp flag.
     */
    private function fillCommon(array &$data): void
    {
        $models = [
            'fleet'       => new FleetModel(),
            'news'        => new NewsModel(),
            'faq'         => new FaqModel(),
            'gallery'     => new GalleryModel(),
        ];
        foreach ($models as $key => $model) {
            foreach (['published', 'draft'] as $status) {
                $data['content'][$key][$status] = (int) $model->builder()->where('status', $status)->countAllResults();
            }
        }

        $news = (new NewsModel())->notDeleted()
            ->orderBy('publish_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll(5);
        $latest = [];
        foreach ($news as $n) {
            $latest[] = [
                'id'           => (int) $n['id'],
                'title'        => (string) ($n['title_en'] ?? ''),
                'publish_date' => $n['publish_date'] ?? null,
            ];
        }
        $data['latest_news'] = $latest;

        $login = (new AdminUserModel())->builder()->selectMax('last_login_at', 'last_login')->get()->getRowArray();
        $data['last_admin_login'] = ! empty($login['last_login']) ? $login['last_login'] : null;

        $config = (new SiteConfigModel())->getConfig();
        $data['whatsapp_configured'] = trim((string) ($config['whatsapp_number'] ?? '')) !== '';
    }

    private function respond200(string $message, array $data): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => $message,
            'data'    => $data,
            'total'   => 0,
        ]);
    }
}
