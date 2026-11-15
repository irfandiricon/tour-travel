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
<div class="dt-toolbar">
    <label class="dt-length">
        Show
        <select class="form-select form-select-sm" data-page-size aria-label="Rows per page">
            <?php foreach ([10, 25, 50, 100] as $size): ?>
                <option value="<?= $size ?>"<?= $size === 10 ? ' selected' : '' ?>><?= $size ?></option>
            <?php endforeach; ?>
        </select>
        entries
    </label>
    <div class="dt-search" role="search">
        <label for="list-search" class="dt-search__label">Search:</label>
        <input type="search" id="list-search" class="form-control form-control-sm" data-list-search
               maxlength="100" autocomplete="off" spellcheck="false" aria-controls="list-table" aria-describedby="list-status">
    </div>
</div>
<div class="list-card table-responsive bg-white border rounded">
    <table id="list-table" class="admin-table table table-hover align-middle mb-0" data-admin-list data-entity="<?= esc($entity) ?>" data-page="<?= esc($page) ?>"
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
<div class="dt-footer">
    <p id="list-status" class="dt-info" aria-live="polite">Loading…</p>
    <nav class="dt-pager" aria-label="Pagination" data-admin-pager hidden>
        <ul class="pagination pagination-sm mb-0" data-page-list></ul>
    </nav>
</div>