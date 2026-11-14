<?php
/**
 * One gallery figure.
 *
 * @var array    $g
 * @var callable $imgUrl
 * @var string   $placeholder
 */
$gImg     = trim((string) ($g['image'] ?? ''));
$gCaption = trim((string) ($g['caption'] ?? ''));
$t        = is_array($t ?? null) ? $t : [];
$zoomable = $gImg !== '' && ! str_ends_with($gImg, 'fleet-placeholder.svg');
$zoomLabel = (string) ($t['zoomImage'] ?? 'View larger image');
?>
<figure class="gallery-item">
    <?php if ($zoomable): ?>
        <button type="button" class="zoomable" data-lightbox data-lightbox-group="gallery"
                data-lightbox-src="<?= esc($imgUrl($gImg), 'attr') ?>" data-lightbox-caption="<?= esc($gCaption, 'attr') ?>"
                aria-label="<?= esc($zoomLabel . ($gCaption !== '' ? ': ' . $gCaption : ''), 'attr') ?>">
    <?php endif; ?>
    <img src="<?= esc($gImg !== '' ? $imgUrl($gImg) : $placeholder, 'attr') ?>" alt="<?= esc((string) ($g['caption'] ?? ''), 'attr') ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= esc($placeholder, 'js') ?>';">
    <?php if ($zoomable): ?>
            <i class="bi bi-zoom-in zoom-icon" aria-hidden="true"></i>
        </button>
    <?php endif; ?>
    <?php if ($gCaption !== ''): ?>
        <figcaption><?= esc((string) $g['caption']) ?></figcaption>
    <?php endif; ?>
</figure>