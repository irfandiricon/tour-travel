<?php
/**
 * One fleet card.
 *
 * @var array    $item
 * @var array    $t           labels (book, readyToBook, imageAlt)
 * @var callable $imgUrl
 * @var string   $placeholder
 * @var string   $trackUrl
 */
$img       = trim((string) ($item['image'] ?? ''));
$fleetName = (string) ($item['name'] ?? '');
$fleetWa   = $item['whatsapp'] ?? null;
// The placeholder picture is not worth enlarging.
$zoomable  = $img !== '' && ! str_ends_with($img, 'fleet-placeholder.svg');
$zoomLabel = (string) ($t['zoomImage'] ?? 'View larger image');
?>
<article class="fleet-card">
    <div class="fleet-card__media">
        <?php if ($zoomable): ?>
            <button type="button" class="zoomable" data-lightbox data-lightbox-group="fleet"
                    data-lightbox-src="<?= esc($imgUrl($img), 'attr') ?>" data-lightbox-caption="<?= esc($fleetName, 'attr') ?>"
                    aria-label="<?= esc($zoomLabel . ($fleetName !== '' ? ': ' . $fleetName : ''), 'attr') ?>">
        <?php endif; ?>
        <img class="fleet-img" src="<?= esc($img !== '' ? $imgUrl($img) : $placeholder, 'attr') ?>" alt="<?= esc($fleetName !== '' ? $fleetName : $t['imageAlt'], 'attr') ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= esc($placeholder, 'js') ?>';">
        <?php if ($zoomable): ?>
                <i class="bi bi-zoom-in zoom-icon" aria-hidden="true"></i>
            </button>
        <?php endif; ?>
        <?php if ($fleetWa !== null): ?>
            <span class="chip-lime"><?= esc($t['readyToBook']) ?></span>
        <?php endif; ?>
    </div>
    <div class="fleet-card__body">
        <h3 class="h5 fleet-card__title"><?= esc($fleetName) ?></h3>
        <?php if (trim((string) ($item['description'] ?? '')) !== ''): ?>
            <p class="fleet-card__text"><?= esc((string) $item['description']) ?></p>
        <?php endif; ?>
        <?php if ($fleetWa !== null): ?>
            <a class="btn btn-wa btn-success mt-auto" href="<?= esc($fleetWa, 'attr') ?>" target="_blank" rel="noopener"
               data-track-booking="<?= esc($trackUrl, 'attr') ?>"
               data-fleet-id="<?= esc((string) ($item['id'] ?? ''), 'attr') ?>"
               data-fleet-name="<?= esc($fleetName, 'attr') ?>"><i class="bi bi-whatsapp" aria-hidden="true"></i> <?= esc($t['book']) ?></a>
        <?php endif; ?>
    </div>
</article>