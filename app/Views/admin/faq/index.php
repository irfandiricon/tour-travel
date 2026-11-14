<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?= view('admin/partials/list_table', [
    'entity'  => 'faq',
    'page'    => 'faq',
    'title'   => $title,
    'columns' => ['question_en' => 'Question (EN)', 'status' => 'Status'],
]) ?>
<?= $this->endSection() ?>
