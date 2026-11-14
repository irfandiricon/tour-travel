<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?= view('admin/partials/list_table', [
    'entity'  => 'fleet',
    'page'    => 'fleet',
    'title'   => $title,
    'columns' => ['name_en' => 'Name (EN)', 'status' => 'Status'],
]) ?>
<?= $this->endSection() ?>
