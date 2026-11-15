<?php
helper('ui');
$loggedIn = session()->get('admin_logged_in') === true;
$username = trim((string) session()->get('admin_username'));
if ($username === '') {
    $username = 'Admin';
}
$path = trim((string) uri_string(), '/');
$primaryNav = [
    ['path' => 'dashboard',         'url' => site_url('dashboard'),         'icon' => 'bi-speedometer2',    'label' => 'Dashboard'],
    ['path' => 'admin/news',        'url' => base_url('admin/news'),        'icon' => 'bi-newspaper',       'label' => 'News & Information'],
    ['path' => 'admin/fleet',       'url' => base_url('admin/fleet'),       'icon' => 'bi-truck-front',     'label' => 'Fleet'],
    ['path' => 'admin/faq',         'url' => base_url('admin/faq'),         'icon' => 'bi-question-circle', 'label' => 'FAQ'],
    ['path' => 'admin/gallery',     'url' => base_url('admin/gallery'),     'icon' => 'bi-images',          'label' => 'Gallery'],
    ['path' => 'admin/testimonials','url' => base_url('admin/testimonials'),'icon' => 'bi-star-half',       'label' => 'Customer Reviews'],
    ['path' => 'admin/users',       'url' => base_url('admin/users'),       'icon' => 'bi-people',          'label' => 'Master User'],
];
$siteConfigActive = ui_nav_is_active($path, 'admin/site-config');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <?= ui_favicon_tags() ?>
    <meta name="csrf-header" content="<?= esc(csrf_header()) ?>">
    <meta name="csrf-name" content="<?= esc(csrf_token()) ?>">
    <meta name="csrf-token" content="<?= esc(csrf_hash()) ?>">
    <meta name="csrf-cookie" content="<?= esc(config('Security')->cookieName) ?>">
    <meta name="base-url" content="<?= esc(base_url()) ?>">
    <title><?= esc($title ?? 'Admin') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="admin-body">
<?php if ($loggedIn): ?>
    <div class="admin-frame d-lg-flex">
        <aside id="adminSidebar" class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1"
               aria-labelledby="adminSidebarLabel">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h5" id="adminSidebarLabel">Travel Admin</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                        data-bs-target="#adminSidebar" aria-label="Close menu"></button>
            </div>
            <div class="offcanvas-body flex-column p-0">
                <div class="admin-brand d-none d-lg-flex align-items-center gap-2 px-3 py-3">
                    <span class="brand-mark" aria-hidden="true"><i class="bi bi-compass"></i></span>
                    <span>Travel Admin</span>
                </div>
                <div class="dropdown px-3 pb-3">
                    <button type="button" class="profile-card dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <span class="avatar" aria-hidden="true"><?= esc(ui_initials($username)) ?></span>
                        <span class="profile-text">
                            <span class="profile-name"><?= esc($username) ?></span>
                            <span class="profile-role">Admin</span>
                        </span>
                        <i class="bi bi-chevron-expand ms-auto" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu w-100">
                        <li><a class="dropdown-item" href="<?= esc(base_url('admin/site-config'), 'attr') ?>">Site config</a></li>
                        <li><a class="dropdown-item" href="<?= esc(site_url('signout'), 'attr') ?>">Sign out</a></li>
                    </ul>
                </div>
                <nav aria-label="Admin" class="flex-grow-1">
                    <ul class="nav flex-column admin-nav">
                        <?php foreach ($primaryNav as $item): ?>
                            <?php $active = ui_nav_is_active($path, $item['path']); ?>
                            <li class="nav-item">
                                <a class="nav-link<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>
                                   href="<?= esc($item['url'], 'attr') ?>">
                                    <i class="bi <?= esc($item['icon'], 'attr') ?>" aria-hidden="true"></i>
                                    <span><?= esc($item['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr class="admin-nav-divider">
                    <ul class="nav flex-column admin-nav admin-nav--secondary">
                        <li class="nav-item">
                            <a class="nav-link<?= $siteConfigActive ? ' active' : '' ?>"<?= $siteConfigActive ? ' aria-current="page"' : '' ?>
                               href="<?= esc(base_url('admin/site-config'), 'attr') ?>">
                                <i class="bi bi-gear" aria-hidden="true"></i><span>Site config</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= esc(base_url(), 'attr') ?>" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span>View website</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= esc(site_url('signout'), 'attr') ?>">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Sign out</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="admin-content flex-grow-1">
            <header class="admin-topbar d-flex d-lg-none align-items-center gap-2 px-3 py-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="offcanvas"
                        data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Open menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <span class="fw-semibold">Travel Admin</span>
            </header>
            <main class="admin-main container-fluid p-3 p-lg-4">
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>
<?php else: ?>
    <main class="admin-main admin-auth admin-auth--bleed">
        <?= $this->renderSection('content') ?>
    </main>
<?php endif; ?>
    <div id="toast-region" class="toast-container toast-region position-fixed bottom-0 end-0 p-3"
         role="status" aria-live="polite" aria-atomic="false"></div>
    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/admin.js') ?>" defer></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
