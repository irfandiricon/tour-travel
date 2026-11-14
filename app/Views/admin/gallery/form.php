<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php $item = $item ?? []; ?>
<?= view('admin/partials/form_open', ['entity' => 'gallery', 'page' => 'gallery', 'title' => $title, 'item' => $item]) ?>
    <?= view('admin/partials/image_field', ['item' => $item, 'imageRequired' => true]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'caption', 'label' => 'Caption', 'type' => 'text', 'required' => false, 'maxlength' => 255, 'item' => $item]) ?>
    <div class="form-row col-12 col-md-6">
        <label for="sort_order" class="form-label">Sort order</label>
        <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" step="1" value="<?= esc((string) ($item['sort_order'] ?? 0)) ?>" aria-describedby="error-sort_order">
        <div class="field-error invalid-feedback" id="error-sort_order" data-error-for="sort_order" role="alert" hidden></div>
    </div>
    <?= view('admin/partials/status_field', ['item' => $item]) ?>
<?= view('admin/partials/form_close', ['page' => 'gallery']) ?>
<?= $this->endSection() ?>
