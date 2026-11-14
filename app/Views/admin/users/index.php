<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?= view('admin/partials/list_table', [
    'entity'  => 'users',
    'page'    => 'users',
    'title'   => $title,
    'columns' => ['display_name' => 'Username', 'status_label' => 'Status', 'last_login_label' => 'Last sign-in'],
]) ?>
<?= $this->endSection() ?>