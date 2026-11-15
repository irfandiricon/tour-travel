<?php
/**
 * Admin dashboard (Requirements 8.1-8.5, 8.12-8.17, 11.7, 11.9).
 * $summary is the proxy array {status, message, data}; a bare `data` array is accepted too.
 * Every key is optional so a missing or partial summary renders zeros instead of failing.
 */
helper('ui');

$summary  = isset($summary) && is_array($summary) ? $summary : [];
$data     = isset($summary['data']) && is_array($summary['data']) ? $summary['data'] : (isset($summary['visitors']) ? $summary : []);
$apiMsg   = is_string($summary['message'] ?? null) ? trim($summary['message']) : '';
$apiOk    = (int) ($summary['status'] ?? 200) === 200;

$arr = static fn ($v): array => is_array($v) ? $v : [];
$int = static fn ($v): int => is_numeric($v) ? (int) $v : 0;

$visitors = $arr($data['visitors'] ?? null);
$bookings = $arr($data['bookings'] ?? null);
$content  = $arr($data['content'] ?? null);
$referrers = $arr($data['referrers'] ?? null);
$topFleet  = $arr($bookings['top_fleet'] ?? null);
$latest    = $arr($data['latest_news'] ?? null);
$lastLogin = $data['last_admin_login'] ?? null;
$waOk      = ! empty($data['whatsapp_configured']);

$hasData = $int($visitors['page_views'] ?? 0) > 0 || $int($visitors['total'] ?? 0) > 0 || $int($bookings['total'] ?? 0) > 0;

$visitorCards = [
    // A visitor is recognised per day only (the hash rotates daily), so the multi-day cards add up
    // daily visitors: someone who comes on 3 days counts 3. See the note under the cards.
    ['label' => 'Daily visitors (all time)',    'value' => $int($visitors['total'] ?? 0),        'icon' => 'bi-people'],
    ['label' => 'Visitors today',               'value' => $int($visitors['unique_today'] ?? 0), 'icon' => 'bi-person-check'],
    ['label' => 'Daily visitors (last 7 days)', 'value' => $int($visitors['unique_7d'] ?? 0),    'icon' => 'bi-calendar-week'],
    ['label' => 'Daily visitors (last 30 days)', 'value' => $int($visitors['unique_30d'] ?? 0),  'icon' => 'bi-calendar-month'],
    ['label' => 'Page views',         'value' => $int($visitors['page_views'] ?? 0),   'icon' => 'bi-eye'],
    ['label' => 'Booking clicks',     'value' => $int($bookings['total'] ?? 0),        'icon' => 'bi-whatsapp'],
    ['label' => 'Booking clicks (7 days)',  'value' => $int($bookings['last_7d'] ?? 0),  'icon' => 'bi-whatsapp'],
    ['label' => 'Booking clicks (30 days)', 'value' => $int($bookings['last_30d'] ?? 0), 'icon' => 'bi-whatsapp'],
];

$entities = [
    'fleet' => 'Fleet', 'news' => 'News & Information', 'faq' => 'FAQ', 'gallery' => 'Gallery',
];

// Proportional bar width (0-100) relative to the largest count in a list.
$maxOf = static function (array $rows) use ($arr, $int): int {
    $max = 0;
    foreach ($rows as $r) {
        $max = max($max, $int($arr($r)['count'] ?? 0));
    }

    return $max;
};
$pct = static fn (int $n, int $max): int => $max > 0 ? (int) round($n / $max * 100) : 0;

$referrerMax = $maxOf($referrers);
$fleetMax    = $maxOf($topFleet);

$welcome  = isset($username) && is_string($username) && $username !== '' ? 'Welcome, ' . $username : 'Welcome';
$subtitle = ui_admin_date(new \DateTimeImmutable('now')) . ' · ' . $welcome;

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?= $this->include('admin/partials/page_header', ['title' => 'Dashboard', 'subtitle' => $subtitle]) ?>

<?php if (! $apiOk || ($apiMsg !== '' && strtoupper($apiMsg) !== 'OK')): ?>
    <div class="alert alert-warning" role="alert"><?= esc($apiMsg !== '' ? $apiMsg : 'Dashboard data is unavailable.') ?></div>
<?php endif; ?>

<?php if (! $waOk): ?>
    <div class="card border-warning mb-4" id="whatsapp-warning">
        <div class="card-body d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-exclamation-triangle text-warning fs-4" aria-hidden="true"></i>
            <span class="flex-grow-1">WhatsApp number is not configured, so booking buttons are hidden on the public site.</span>
            <a class="btn btn-warning btn-sm" href="<?= base_url('admin/site-config') ?>">Open Site Configuration</a>
        </div>
    </div>
<?php endif; ?>

<?php if (! $hasData): ?>
    <div class="alert alert-info" role="status" id="dashboard-empty">
        No visitor or booking data has been recorded yet. Statistics will appear once the public site receives traffic.
    </div>
<?php endif; ?>

<section aria-label="Visitor and booking statistics" class="row g-3 mb-4">
    <?php foreach ($visitorCards as $card): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="text-muted small"><i class="bi <?= esc($card['icon'], 'attr') ?>" aria-hidden="true"></i> <?= esc($card['label']) ?></div>
                        <div class="fs-3 fw-semibold"><?= number_format($card['value']) ?></div>
                    </div>
                    <span class="stat-card__tile" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <p class="col-12 small text-muted mb-0" id="visitor-note">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        Daily visitors: each visitor is counted once per day (same device and browser). A visitor who comes back on
        another day is counted again, so the 7- and 30-day figures are the sum of the daily counts, not distinct people.
        Bots and admin pages are excluded.
    </p>
</section>

<section aria-label="Content counts" class="row g-3 mb-4">
    <?php foreach ($entities as $key => $label): $e = $arr($content[$key] ?? null); ?>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="text-muted small"><?= esc($label) ?></div>
                        <div><span class="badge text-bg-success">Published <?= $int($e['published'] ?? 0) ?></span></div>
                        <div class="mt-1"><span class="badge text-bg-secondary">Draft <?= $int($e['draft'] ?? 0) ?></span></div>
                    </div>
                    <a class="stat-card__tile stat-card__tile--link" href="<?= base_url('admin/' . $key) ?>" aria-label="Open <?= esc($label, 'attr') ?>">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section aria-label="Charts" class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="card chart-card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Daily visitors</span>
                <span class="d-inline-flex align-items-center gap-2">
                    <label for="daily-range" class="small text-muted mb-0">Range</label>
                    <select id="daily-range" class="form-select form-select-sm w-auto">
                        <option value="7">Last 7 days</option>
                        <option value="14">Last 14 days</option>
                        <option value="30" selected>Last 30 days</option>
                    </select>
                </span>
            </div>
            <div class="card-body" style="height:300px">
                <canvas id="chart-daily" role="img" aria-label="Daily page views and daily visitors for the selected range"></canvas>
                <p id="chart-daily-empty" class="text-muted text-center my-4 d-none">No data available yet.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card chart-card h-100">
            <div class="card-header">Last 7 days</div>
            <div class="card-body" style="height:300px">
                <canvas id="chart-weekly" role="img" aria-label="Page views compared with daily visitors for the last 7 days"></canvas>
                <p id="chart-weekly-empty" class="text-muted text-center my-4 d-none">No data available yet.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card donut-card h-100">
            <div class="card-header">Language distribution</div>
            <div class="card-body">
                <div class="position-relative" style="height:220px">
                    <canvas id="chart-language" role="img" aria-label="Visitors by language"></canvas>
                    <div class="donut-center" aria-hidden="true"><span id="total-language">0</span></div>
                    <p id="chart-language-empty" class="text-muted text-center my-4 d-none">No data available yet.</p>
                </div>
                <ul class="donut-legend list-unstyled mb-0 mt-3" id="legend-language"></ul>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card donut-card h-100">
            <div class="card-header">Device distribution</div>
            <div class="card-body">
                <div class="position-relative" style="height:220px">
                    <canvas id="chart-device" role="img" aria-label="Visitors by device type"></canvas>
                    <div class="donut-center" aria-hidden="true"><span id="total-device">0</span></div>
                    <p id="chart-device-empty" class="text-muted text-center my-4 d-none">No data available yet.</p>
                </div>
                <ul class="donut-legend list-unstyled mb-0 mt-3" id="legend-device"></ul>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card list-card h-100">
            <div class="card-header">Top referrers</div>
            <?php if ($referrers === []): ?>
                <p class="text-muted card-body mb-0">No referrer data yet.</p>
            <?php else: ?>
                <ol class="list-card__list list-unstyled mb-0 card-body">
                    <?php foreach ($referrers as $r): $r = $arr($r); $n = $int($r['count'] ?? 0); ?>
                        <li class="list-card__item">
                            <div class="d-flex justify-content-between gap-2">
                                <span><?= esc((string) ($r['label'] ?? $r['key'] ?? $r['name'] ?? '')) ?></span>
                                <span class="fw-semibold"><?= $n ?></span>
                            </div>
                            <div class="list-card__bar" aria-hidden="true"><span style="width:<?= $pct($n, $referrerMax) ?>%"></span></div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card list-card h-100">
            <div class="card-header">Top 5 fleet by booking clicks</div>
            <?php if ($topFleet === []): ?>
                <p class="text-muted card-body mb-0">No booking clicks yet.</p>
            <?php else: ?>
                <ol class="list-card__list list-unstyled mb-0 card-body">
                    <?php foreach ($topFleet as $f): $f = $arr($f); $n = $int($f['count'] ?? 0); ?>
                        <li class="list-card__item">
                            <div class="d-flex justify-content-between gap-2">
                                <span><?= esc((string) ($f['label'] ?? $f['key'] ?? $f['name'] ?? '')) ?></span>
                                <span class="fw-semibold"><?= $n ?></span>
                            </div>
                            <div class="list-card__bar" aria-hidden="true"><span style="width:<?= $pct($n, $fleetMax) ?>%"></span></div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card list-card h-100">
            <div class="card-header">Latest news</div>
            <ul class="list-group list-group-flush">
                <?php if ($latest === []): ?>
                    <li class="list-group-item text-muted">No news yet.</li>
                <?php else: foreach ($latest as $n): $n = $arr($n); ?>
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <span class="list-card__icon" aria-hidden="true"><i class="bi bi-newspaper"></i></span>
                        <span class="flex-grow-1 fw-semibold"><?= esc((string) ($n['title'] ?? '')) ?></span>
                        <small class="badge-date"><?= esc((string) ($n['publish_date'] ?? '')) ?></small>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card list-card h-100">
            <div class="card-header">Last admin login</div>
            <div class="card-body">
                <?= is_string($lastLogin) && $lastLogin !== '' ? esc($lastLogin) : '<span class="text-muted">No login recorded.</span>' ?>
            </div>
        </div>
    </div>
</section>

<script type="application/json" id="dashboard-data"><?= json_encode(['data' => $data], $jsonFlags) ?: '{"data":{}}' ?></script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= base_url('assets/js/dashboard.js') ?>" defer></script>
<?= $this->endSection() ?>
