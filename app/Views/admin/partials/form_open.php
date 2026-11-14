<?php
/**
 * Opening markup for an AJAX form. Pair with partials/form_close.
 *
 * @var string     $entity
 * @var string     $page
 * @var array|null $item
 * @var string     $title
 * @var string|null $legend  text after the * marker (defaults to the multilingual content note)
 */
$page = $page ?? $entity;
$id   = $item['id'] ?? null;
$legend = $legend ?? 'Required. English (EN) is the default language and must be filled in.';
?>
<h1 class="h3 mb-3"><?= esc($title) ?></h1>
<p class="legend text-body-secondary small"><span class="req" aria-hidden="true">*</span> <?= esc($legend) ?></p>
<div id="form-summary" class="alert alert-danger" role="alert" tabindex="-1" hidden></div>
<form data-admin-form data-entity="<?= esc($entity) ?>" data-id="<?= esc((string) ($id ?? '')) ?>"
      data-redirect="<?= esc(base_url('admin/' . $page)) ?>"
      method="post" enctype="multipart/form-data" novalidate class="row g-3">
    <?= csrf_field() ?>