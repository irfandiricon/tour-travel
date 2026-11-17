<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$item  = $item ?? [];
$price = isset($item['price_from']) && $item['price_from'] !== null && $item['price_from'] !== ''
    ? 'Rp ' . number_format((int) $item['price_from'], 0, ',', '.')
    : '';
?>
<?= view('admin/partials/form_open', ['entity' => 'fleet', 'page' => 'fleet', 'title' => $title, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 200, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'item' => $item]) ?>
    <div class="form-row col-12 col-md-6">
        <label for="price_from" class="form-label">Harga dimulai dari</label>
        <input type="text" inputmode="numeric" class="form-control" id="price_from" name="price_from" maxlength="20"
               placeholder="Rp 1.100.000" value="<?= esc($price, 'attr') ?>" data-currency="idr" autocomplete="off"
               aria-describedby="hint-price_from error-price_from">
        <div class="form-text" id="hint-price_from">Optional. Type the amount in rupiah, it is formatted as currency while you type (1100000 becomes Rp 1.100.000). Shown on the fleet card and in the booking form; leave empty to hide the price.</div>
        <div class="field-error invalid-feedback" id="error-price_from" data-error-for="price_from" role="alert" hidden></div>
    </div>
    <?= view('admin/partials/image_field', ['item' => $item, 'imageRequired' => false]) ?>
    <?= view('admin/partials/status_field', ['item' => $item]) ?>
<?= view('admin/partials/form_close', ['page' => 'fleet']) ?>
<?= $this->endSection() ?>
