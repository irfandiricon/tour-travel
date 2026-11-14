<?php
/**
 * Generic AJAX-populated list table.
 *
 * @var string               $entity   Entity slug used in admin/api/<entity>/... and admin/<page>/...
 * @var string               $page     Admin page slug (admin/<page>), defaults to $entity
 * @var array<string,string> $columns  data key => column heading
 * @var string               $title
 */
$page    = $page ?? $entity;
$flash   = session()->getFlashdata('error');
?>
<?= view('admin/partials/page_header', [
    'title'   => $title,
    'actions' => '<a class="btn btn-primary" href="' . esc(base_url('admin/' . $page . '/create'), 'attr') . '">'
        . '<i class="bi bi-plus-lg" aria-hidden="true"></i> Create new</a>',
]) ?>
<?php if (! empty($flash)): ?>
    <div role="alert" class="alert alert-danger"><?= esc($flash) ?></div>
<?php endif; ?>
<div class="list-card table-responsive bg-white border rounded">
    <table class="admin-table table table-hover align-middle mb-0" data-admin-list data-entity="<?= esc($entity) ?>" data-page="<?= esc($page) ?>"
           aria-describedby="list-status">
        <caption class="sr-only"><?= esc($title) ?></caption>
        <thead class="table-light">
            <tr>
                <th scope="col">No</th>
                <?php foreach ($columns as $key => $heading): ?>
                    <th scope="col" data-key="<?= esc($key) ?>"><?= esc($heading) ?></th>
                <?php endforeach; ?>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<p id="list-status" class="text-body-secondary small mt-2" aria-live="polite">Loading…</p>
<nav class="pager" aria-label="Pagination" data-admin-pager hidden>
    <button type="button" class="btn btn-outline-secondary btn-sm" data-page-prev><i class="bi bi-chevron-left" aria-hidden="true"></i> Previous</button>
    <span data-page-info></span>
    <button type="button" class="btn btn-outline-secondary btn-sm" data-page-next>Next <i class="bi bi-chevron-right" aria-hidden="true"></i></button>
</nav>