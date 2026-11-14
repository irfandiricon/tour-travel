<?php
/**
 * One News & Information card: picture on top, then title, date and a short preview.
 * The whole card links to the detail page when the item has a `url`.
 *
 * @var array         $n       title, body, excerpt, publish_date, image, url (all optional)
 * @var array|null    $t       labels (readMore)
 * @var callable|null $imgUrl  path -> absolute URL
 */
$t        = is_array($t ?? null) ? $t : [];
$imgUrl   = $imgUrl ?? static fn (string $p): string => preg_match('#^https?://#i', $p) === 1 ? $p : base_url(ltrim($p, '/'));
$image    = trim((string) ($n['image'] ?? ''));
$title    = (string) ($n['title'] ?? '');
$url      = trim((string) ($n['url'] ?? ''));
$date     = trim((string) ($n['publish_date'] ?? ''));
$dateText = trim((string) ($n['date_label'] ?? '')) !== '' ? (string) $n['date_label'] : substr($date, 0, 10);
$preview  = (string) ($n['excerpt'] ?? ($n['body'] ?? ''));
$readMore = (string) ($t['readMore'] ?? 'Read more');
$tag      = $url !== '' ? 'a' : 'div';
?>
<article class="news-card">
    <<?= $tag ?> class="news-card__link"<?= $url !== '' ? ' href="' . esc($url, 'attr') . '"' : '' ?>>
        <?php if ($image !== ''): ?>
            <div class="news-card__media">
                <img src="<?= esc($imgUrl($image), 'attr') ?>" alt="<?= esc($title, 'attr') ?>" loading="lazy">
            </div>
        <?php endif; ?>
        <div class="news-card__body">
            <h3 class="h5 news-card__title"><?= esc($title) ?></h3>
            <?php if ($date !== ''): ?>
                <time class="card-date" datetime="<?= esc(substr($date, 0, 10), 'attr') ?>"><?= esc($dateText) ?></time>
            <?php endif; ?>
            <p class="news-card__text"><?= esc($preview) ?></p>
            <?php if ($url !== ''): ?>
                <span class="news-card__more"><?= esc($readMore) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
            <?php endif; ?>
        </div>
    </<?= $tag ?>>
</article>