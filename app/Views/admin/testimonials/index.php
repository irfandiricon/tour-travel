<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?= view('admin/partials/list_table', [
    'entity'  => 'testimonials',
    'page'    => 'testimonials',
    'title'   => $title,
    'columns' => ['customer_name' => 'Customer', 'stars' => 'Rating', 'status' => 'Status'],
]) ?>
<?= $this->endSection() ?>