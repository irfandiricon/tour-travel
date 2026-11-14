<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php $item = $item ?? []; ?>
<?= view('admin/partials/form_open', ['entity' => 'fleet', 'page' => 'fleet', 'title' => $title, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 200, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'item' => $item]) ?>
    <?= view('admin/partials/image_field', ['item' => $item, 'imageRequired' => false]) ?>
    <?= view('admin/partials/status_field', ['item' => $item]) ?>
<?= view('admin/partials/form_close', ['page' => 'fleet']) ?>
<?= $this->endSection() ?>
