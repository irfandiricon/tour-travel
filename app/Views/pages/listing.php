<?php
/**
 * Full listing page for Fleet, Gallery or News & Information.
 *
 * @var string $lang
 * @var string $brand
 * @var string $kind        fleet|gallery|news
 * @var string $title
 * @var string $subtitle
 * @var string $emptyText
 * @var array  $items       items of the current page
 * @var array  $pagination  page, pages, total, perPage
 * @var array  $labels      ui_page_labels()
 * @var string $trackBookingUrl
 */

helper('ui');

$t        = $labels;
$page     = (int) ($pagination['page'] ?? 1);
$pages    = (int) ($pagination['pages'] ?? 1);
$homeUrl  = site_url('/');
$trackUrl = (string) ($trackBookingUrl ?? '');

$imgUrl = static function (string $path): string {
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return base_url(ltrim($path, '/'));
};
$placeholder = $imgUrl('assets/img/fleet-placeholder.svg');

$pageUrl = static fn (int $n): string => $n > 1 ? site_url($kind) . '?page=' . $n : site_url($kind);

// Page numbers to show: all when few, otherwise first, last and the neighbours of the current page (0 = gap).
$numbers = [];
if ($pages <= 7) {
    $numbers = range(1, $pages);
} else {
    $keep = array_unique(array_filter([1, $pages, $page - 1, $page, $page + 1], static fn (int $n): bool => $n >= 1 && $n <= $pages));
    sort($keep);
    $previous = 0;
    foreach ($keep as $n) {
        if ($previous !== 0 && $n - $previous > 1) {
            $numbers[] = 0;
        }
        $numbers[] = $n;
        $previous  = $n;
    }
}

$this->extend('layouts/public');
$this->setVar('brand', $brand);
$this->setVar('navActive', $kind);
$this->setVar('navBase', $homeUrl);
?>

<?= $this->section('content') ?>
<section id="top" class="page-hero" aria-labelledby="page-title">
    <div class="container">
        <nav aria-label="<?= esc($t['breadcrumb'], 'attr') ?>">
            <ol class="breadcrumb page-crumbs">
                <li class="breadcrumb-item"><a href="<?= esc($homeUrl, 'attr') ?>"><?= esc($t['home']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li>
            </ol>
        </nav>
        <h1 id="page-title" class="page-title"><?= esc($title) ?></h1>
        <p class="section-sub page-sub"><?= esc($subtitle) ?></p>
    </div>
</section>

<section class="section listing listing--<?= esc($kind, 'attr') ?>" aria-labelledby="page-title">
    <div class="container">
        <?php if ($items === []): ?>
            <p class="empty-state"><?= esc($emptyText) ?></p>
        <?php else: ?>
            <?php if ($kind === 'fleet'): ?>
                <div class="fleet-grid">
                    <?php foreach ($items as $item): ?>
                        <?= ui_partial('fleet_card', ['item' => $item, 't' => $t, 'imgUrl' => $imgUrl, 'placeholder' => $placeholder, 'trackUrl' => $trackUrl]) ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($kind === 'gallery'): ?>
                <div class="gallery-grid gallery-grid--page">
                    <?php foreach ($items as $g): ?>
                        <?= ui_partial('gallery_item', ['g' => $g, 't' => $t, 'imgUrl' => $imgUrl, 'placeholder' => $placeholder]) ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($kind === 'news'): ?>
                <div class="news-grid news-grid--page">
                    <?php foreach ($items as $n): ?>
                        <?= ui_partial('news_card', ['n' => $n, 'featured' => false, 't' => $t, 'imgUrl' => $imgUrl]) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($pages > 1): ?>
                <nav class="listing-pagination" aria-label="<?= esc($t['pagination'], 'attr') ?>">
                    <ul class="pagination justify-content-center flex-wrap">
                        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                            <?php if ($page > 1): ?>
                                <a class="page-link" href="<?= esc($pageUrl($page - 1), 'attr') ?>" rel="prev"><?= esc($t['prev']) ?></a>
                            <?php else: ?>
                                <span class="page-link"><?= esc($t['prev']) ?></span>
                            <?php endif; ?>
                        </li>
                        <?php foreach ($numbers as $n): ?>
                            <?php if ($n === 0): ?>
                                <li class="page-item disabled"><span class="page-link" aria-hidden="true">&hellip;</span></li>
                            <?php else: ?>
                                <li class="page-item<?= $n === $page ? ' active' : '' ?>">
                                    <a class="page-link" href="<?= esc($pageUrl($n), 'attr') ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= (int) $n ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
                            <?php if ($page < $pages): ?>
                                <a class="page-link" href="<?= esc($pageUrl($page + 1), 'attr') ?>" rel="next"><?= esc($t['next']) ?></a>
                            <?php else: ?>
                                <span class="page-link"><?= esc($t['next']) ?></span>
                            <?php endif; ?>
                        </li>
                    </ul>
                    <p class="listing-pagination__info"><?= esc(str_replace(['{n}', '{m}'], [(string) $page, (string) $pages], $t['pageOf'])) ?></p>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <div class="section-more">
            <a class="btn-more" href="<?= esc($homeUrl, 'attr') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= esc($t['backHome']) ?></a>
        </div>
    </div>
</section>
<?= $this->endSection() ?>