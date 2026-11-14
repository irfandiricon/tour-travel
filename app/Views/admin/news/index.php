<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?= view('admin/partials/list_table', [
    'entity'  => 'news',
    'page'    => 'news',
    'title'   => $title,
    'columns' => ['title_en' => 'Title (EN)', 'publish_date' => 'Publish date', 'status' => 'Status'],
]) ?>
<?= $this->endSection() ?>
