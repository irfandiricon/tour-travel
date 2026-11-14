<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php $item = $item ?? []; ?>
<?= view('admin/partials/form_open', ['entity' => 'faq', 'page' => 'faq', 'title' => $title, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'question', 'label' => 'Question', 'type' => 'text', 'required' => true, 'maxlength' => 500, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'required' => true, 'maxlength' => 5000, 'item' => $item]) ?>
    <?= view('admin/partials/status_field', ['item' => $item]) ?>
<?= view('admin/partials/form_close', ['page' => 'faq']) ?>
<?= $this->endSection() ?>
