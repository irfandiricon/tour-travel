<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?= view('admin/partials/list_table', [
    'entity'  => 'gallery',
    'page'    => 'gallery',
    'title'   => $title,
    'columns' => ['caption_en' => 'Caption (EN)', 'sort_order' => 'Order', 'status' => 'Status'],
]) ?>
<?= $this->endSection() ?>
