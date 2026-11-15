<?php
/**
 * One customer review: stars, the review (already in the visitor's language), photo (or initials) and name.
 *
 * @var array         $r       name, photo, rating (1-5), review
 * @var array|null    $t       labels (rated: "Rated {n} out of 5")
 * @var callable|null $imgUrl  path -> absolute URL
 */
helper('ui');
$t      = is_array($t ?? null) ? $t : [];
$imgUrl = $imgUrl ?? static fn (string $p): string => preg_match('#^https?://#i', $p) === 1 ? $p : base_url(ltrim($p, '/'));
$name   = (string) ($r['name'] ?? '');
$photo  = trim((string) ($r['photo'] ?? ''));
$rating = max(0, min(5, (int) ($r['rating'] ?? 0)));
$rated  = str_replace('{n}', (string) $rating, (string) ($t['rated'] ?? 'Rated {n} out of 5'));
?>
<figure class="testi-card">
    <div class="testi-card__stars" role="img" aria-label="<?= esc($rated, 'attr') ?>">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="bi <?= $i <= $rating ? 'bi-star-fill' : 'bi-star' ?>" aria-hidden="true"></i>
        <?php endfor; ?>
    </div>
    <blockquote class="testi-card__quote">
        <p><?= esc((string) ($r['review'] ?? '')) ?></p>
    </blockquote>
    <figcaption class="testi-card__author">
        <?php if ($photo !== ''): ?>
            <img class="testi-card__avatar" src="<?= esc($imgUrl($photo), 'attr') ?>" alt="" width="52" height="52" loading="lazy">
        <?php else: ?>
            <span class="testi-card__avatar testi-card__avatar--initials" aria-hidden="true"><?= esc(ui_initials($name)) ?></span>
        <?php endif; ?>
        <span class="testi-card__name"><?= esc($name) ?></span>
    </figcaption>
</figure>