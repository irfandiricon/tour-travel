<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$item = $item ?? [];
$publish = '';
if (! empty($item['publish_date'])) {
    $ts      = strtotime((string) $item['publish_date']);
    $publish = $ts ? date('Y-m-d\TH:i', $ts) : '';
}
?>
<?= view('admin/partials/form_open', ['entity' => 'news', 'page' => 'news', 'title' => $title, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'maxlength' => 200, 'item' => $item]) ?>
    <?= view('admin/partials/lang_field', ['base' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => true, 'maxlength' => 20000, 'item' => $item]) ?>
    <div class="form-row col-12 col-md-6">
        <label for="publish_date" class="form-label">Publish date</label>
        <input type="datetime-local" class="form-control" id="publish_date" name="publish_date" value="<?= esc($publish) ?>" aria-describedby="error-publish_date">
        <div class="field-error invalid-feedback" id="error-publish_date" data-error-for="publish_date" role="alert" hidden></div>
    </div>
    <?= view('admin/partials/image_field', ['item' => $item, 'imageRequired' => false, 'removable' => true, 'hint' => 'Optional. Shown on the news card; click it on the website to enlarge. A 16:9 or 3:2 photo works best.'], ['saveData' => false]) ?>
    <?= view('admin/partials/status_field', ['item' => $item]) ?>
<?= view('admin/partials/form_close', ['page' => 'news']) ?>
<?= $this->endSection() ?>
