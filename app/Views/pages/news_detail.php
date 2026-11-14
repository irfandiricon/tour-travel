<?php
/**
 * Detail page of one News & Information item.
 *
 * @var string $lang
 * @var string $brand
 * @var array  $item       id, title, body, excerpt, publish_date, image, path, url
 * @var array  $others     other published items (same shape), newest first
 * @var string $newsTitle  "News & Information" in the current language
 * @var array  $labels     ui_page_labels()
 */

helper('ui');

$t       = $labels;
$homeUrl = site_url('/');
$newsUrl = site_url('news');
$title   = (string) ($item['title'] ?? '');
$image   = trim((string) ($item['image'] ?? ''));
$date    = trim((string) ($item['publish_date'] ?? ''));
$dateText = trim((string) ($item['date_label'] ?? '')) !== '' ? (string) $item['date_label'] : substr($date, 0, 10);
$others  = is_array($others ?? null) ? $others : [];

$imgUrl = static function (string $path): string {
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return base_url(ltrim($path, '/'));
};

$this->extend('layouts/public');
$this->setVar('brand', $brand);
$this->setVar('navActive', 'news');
$this->setVar('navReturn', (string) ($item['id'] ?? '') !== '' ? 'news/' . (int) $item['id'] : 'news');
$this->setVar('navBase', $homeUrl);
?>

<?= $this->section('content') ?>
<section id="top" class="page-hero" aria-labelledby="page-title">
    <div class="container">
        <nav aria-label="<?= esc($t['breadcrumb'], 'attr') ?>">
            <ol class="breadcrumb page-crumbs">
                <li class="breadcrumb-item"><a href="<?= esc($homeUrl, 'attr') ?>"><?= esc($t['home']) ?></a></li>
                <li class="breadcrumb-item"><a href="<?= esc($newsUrl, 'attr') ?>"><?= esc($newsTitle) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li>
            </ol>
        </nav>
        <h1 id="page-title" class="page-title"><?= esc($title) ?></h1>
        <?php if ($date !== ''): ?>
            <p class="section-sub page-sub news-detail__date">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <span><?= esc($t['published']) ?>:</span>
                <time datetime="<?= esc(substr($date, 0, 10), 'attr') ?>"><?= esc($dateText) ?></time>
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="section" aria-labelledby="page-title">
    <div class="container">
        <article class="news-detail">
            <?php if ($image !== ''): ?>
                <div class="news-detail__media">
                    <button type="button" class="zoomable" data-lightbox
                            data-lightbox-src="<?= esc($imgUrl($image), 'attr') ?>" data-lightbox-caption="<?= esc($title, 'attr') ?>"
                            aria-label="<?= esc($t['zoomImage'] . ($title !== '' ? ': ' . $title : ''), 'attr') ?>">
                        <img src="<?= esc($imgUrl($image), 'attr') ?>" alt="<?= esc($title, 'attr') ?>">
                        <i class="bi bi-zoom-in zoom-icon" aria-hidden="true"></i>
                    </button>
                </div>
            <?php endif; ?>
            <div class="news-detail__body"><?= esc((string) ($item['body'] ?? '')) ?></div>
        </article>

        <?php if ($others !== []): ?>
            <aside class="news-related" aria-labelledby="news-related-title">
                <h2 id="news-related-title" class="h4 news-related__title"><?= esc($t['otherNews']) ?></h2>
                <div class="news-grid">
                    <?php foreach ($others as $n): ?>
                        <?= ui_partial('news_card', ['n' => $n, 't' => $t, 'imgUrl' => $imgUrl]) ?>
                    <?php endforeach; ?>
                </div>
            </aside>
        <?php endif; ?>

        <div class="section-more">
            <a class="btn-more" href="<?= esc($newsUrl, 'attr') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= esc($t['backNews']) ?></a>
        </div>
    </div>
</section>
<?= $this->endSection() ?>