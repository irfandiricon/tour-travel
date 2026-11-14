<?php
/**
 * Admin page header: large bold title, date subtitle and an actions slot.
 *
 * @var string      $title    Page title (plain text, escaped here)
 * @var string|null $subtitle Optional subtitle (plain text). Defaults to today's date.
 * @var string|null $actions  Optional trusted HTML for action buttons (e.g. "Create new").
 *                            The caller is responsible for escaping any dynamic values inside it.
 */
helper('ui');

$title    = (string) ($title ?? '');
$subtitle = isset($subtitle) && $subtitle !== '' ? (string) $subtitle : ui_admin_date(new \DateTimeImmutable('now'));
$actions  = isset($actions) ? (string) $actions : '';
?>
<header class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="page-header__text">
        <h1 class="page-header__title mb-1"><?= esc($title) ?></h1>
        <p class="page-header__subtitle text-body-secondary mb-0"><?= esc($subtitle) ?></p>
    </div>
    <?php if ($actions !== ''): ?>
        <div class="page-header__actions d-flex flex-wrap align-items-center gap-2"><?= $actions ?></div>
    <?php endif; ?>
</header>
