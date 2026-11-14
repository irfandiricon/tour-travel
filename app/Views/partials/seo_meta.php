<?php
/**
 * Head SEO metadata rendered from the SeoMetaBuilder output.
 * Tolerates a missing or partial `$seo` variable (other public pages only pass a title).
 *
 * @var array|null  $seo
 * @var string|null $title
 */

use App\Libraries\SeoMetaBuilder;

$seo        = is_array($seo ?? null) ? $seo : [];
$metaTitle  = trim((string) ($seo['title'] ?? ($title ?? '')));
$metaDesc   = trim((string) ($seo['description'] ?? ''));
$metaKeys   = trim((string) ($seo['keywords'] ?? ''));
$metaRobots = trim((string) ($seo['robots'] ?? 'index, follow'));
$canonical  = trim((string) ($seo['canonical'] ?? ''));
$alternates = is_array($seo['alternates'] ?? null) ? $seo['alternates'] : [];
$og         = is_array($seo['og'] ?? null) ? $seo['og'] : [];
$twitter    = is_array($seo['twitter'] ?? null) ? $seo['twitter'] : [];
$jsonld     = is_array($seo['jsonld'] ?? null) ? array_values($seo['jsonld']) : [];
?>
<title><?= esc($metaTitle !== '' ? $metaTitle : 'Travel & Car Rental') ?></title>
<?php if ($metaDesc !== ''): ?>
<meta name="description" content="<?= esc($metaDesc, 'attr') ?>">
<?php endif; ?>
<?php if ($metaKeys !== ''): ?>
<meta name="keywords" content="<?= esc($metaKeys, 'attr') ?>">
<?php endif; ?>
<meta name="robots" content="<?= esc($metaRobots, 'attr') ?>">
<?php if ($canonical !== ''): ?>
<link rel="canonical" href="<?= esc($canonical, 'attr') ?>">
<?php endif; ?>
<?php foreach ($alternates as $alt): ?>
<?php if (is_array($alt) && isset($alt['hreflang'], $alt['href'])): ?>
<link rel="alternate" hreflang="<?= esc((string) $alt['hreflang'], 'attr') ?>" href="<?= esc((string) $alt['href'], 'attr') ?>">
<?php endif; ?>
<?php endforeach; ?>
<?php foreach ($og as $property => $content): ?>
<meta property="<?= esc((string) $property, 'attr') ?>" content="<?= esc((string) $content, 'attr') ?>">
<?php endforeach; ?>
<?php foreach ($twitter as $name => $content): ?>
<meta name="<?= esc((string) $name, 'attr') ?>" content="<?= esc((string) $content, 'attr') ?>">
<?php endforeach; ?>
<?php if ($jsonld !== []): ?>
<script type="application/ld+json"><?= SeoMetaBuilder::encodeJsonLd($jsonld) ?></script>
<?php endif; ?>
