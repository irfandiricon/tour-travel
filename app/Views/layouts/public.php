<?php
/**
 * Public layout: head (SEO), navbar, language dropdown, main, footer,
 * floating WhatsApp button and local JS. Child views fill `content`
 * (and optionally `head` / `scripts`).
 *
 * Optional view data: $lang, $seo, $title, $contact['whatsapp'].
 */
$layoutLabels = [
    'en' => ['brand' => 'Travel & Car Rental', 'skip' => 'Skip to content', 'toggle' => 'Toggle navigation', 'navLabel' => 'Main navigation',
             'langLabel' => 'Language', 'about' => 'About Us', 'fleet' => 'Our Fleet', 'gallery' => 'Gallery', 'news' => 'News & Information',
             'faq' => 'FAQ', 'contact' => 'Contact', 'wa' => 'Chat on WhatsApp', 'footer' => 'All rights reserved.', 'top' => 'Back to top', 'footerNav' => 'Footer navigation',
             'lbLabel' => 'Image preview', 'lbClose' => 'Close', 'lbPrev' => 'Previous image', 'lbNext' => 'Next image'],
    'id' => ['brand' => 'Wisata & Rental Mobil', 'skip' => 'Lewati ke konten', 'toggle' => 'Buka/tutup navigasi', 'navLabel' => 'Navigasi utama',
             'langLabel' => 'Bahasa', 'about' => 'Tentang Kami', 'fleet' => 'Armada Kami', 'gallery' => 'Galeri', 'news' => 'Berita & Informasi',
             'faq' => 'FAQ', 'contact' => 'Kontak', 'wa' => 'Chat via WhatsApp', 'footer' => 'Hak cipta dilindungi.', 'top' => 'Kembali ke atas', 'footerNav' => 'Navigasi footer',
             'lbLabel' => 'Pratinjau gambar', 'lbClose' => 'Tutup', 'lbPrev' => 'Gambar sebelumnya', 'lbNext' => 'Gambar berikutnya'],
    'ms' => ['brand' => 'Pelancongan & Sewa Kereta', 'skip' => 'Langkau ke kandungan', 'toggle' => 'Togol navigasi', 'navLabel' => 'Navigasi utama',
             'langLabel' => 'Bahasa', 'about' => 'Tentang Kami', 'fleet' => 'Armada Kami', 'gallery' => 'Galeri', 'news' => 'Berita & Maklumat',
             'faq' => 'Soalan Lazim', 'contact' => 'Hubungi Kami', 'wa' => 'Sembang di WhatsApp', 'footer' => 'Hak cipta terpelihara.', 'top' => 'Kembali ke atas', 'footerNav' => 'Navigasi pengaki',
             'lbLabel' => 'Pratonton imej', 'lbClose' => 'Tutup', 'lbPrev' => 'Imej sebelumnya', 'lbNext' => 'Imej seterusnya'],
];
helper('ui');
$layoutLang = isset($lang, $layoutLabels[$lang]) ? $lang : 'en';
$L          = $layoutLabels[$layoutLang];
if (trim((string) ($brand ?? '')) !== '') {
    $L['brand'] = trim((string) $brand);
}
$langNames  = ['en' => 'English', 'id' => 'Indonesia', 'ms' => 'Melayu'];

// Navbar logo: trimmed 468x96 copy of uploads/brand/Kimigo Travel Logo with Bridge and Airplane.png
// (WebP with a PNG fallback). Without the file the icon + brand name are shown instead.
$brandLogo = null;
if (is_file(FCPATH . 'uploads/brand/kimigo-travel-logo.png')) {
    $brandLogo = [
        'png'    => 'uploads/brand/kimigo-travel-logo.png',
        'webp'   => is_file(FCPATH . 'uploads/brand/kimigo-travel-logo.webp') ? 'uploads/brand/kimigo-travel-logo.webp' : null,
        'width'  => 468,
        'height' => 96,
    ];
}
// Every menu item scrolls to its section on the landing page. Sub pages (fleet, gallery, news and
// news detail) set `navBase` (landing URL) so the anchors lead back there, and `navActive` (their
// slug) to highlight the matching item. No aria-current: the link goes to the landing section, not
// to the page you are on.
$navBase    = isset($navBase) ? (string) $navBase : '';
$navActive  = isset($navActive) ? (string) $navActive : '';
$navClass   = static fn (string $slug): string => 'nav-link' . ($slug === $navActive ? ' active' : '');
// $navReturn (e.g. news/5 on a detail page) overrides where the language switcher returns to.
$navReturn  = isset($navReturn) && (string) $navReturn !== '' ? (string) $navReturn : $navActive;
$langReturn = $navReturn !== '' ? '?return=' . rawurlencode($navReturn) : '';

$layoutContact = is_array($contact ?? null) ? $contact : [];
$floatingWa    = $layoutContact['whatsapp'] ?? null;
$floatingWa    = is_string($floatingWa) && trim($floatingWa) !== '' ? $floatingWa : null;
?>
<!DOCTYPE html>
<html lang="<?= esc($layoutLang, 'attr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= ui_favicon_tags() ?>
    <?= $this->include('partials/seo_meta', null, false) ?>
    <link rel="preload" href="<?= esc(base_url('assets/vendor/fonts/plus-jakarta-sans/PlusJakartaSans-latin-wght.woff2'), 'attr') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= esc(base_url('assets/vendor/bootstrap/css/bootstrap.min.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/tokens.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/landing.css'), 'attr') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="public-body">
<a class="skip-link" href="#main"><?= esc($L['skip']) ?></a>

<header class="site-header sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="<?= esc($L['navLabel'], 'attr') ?>">
        <div class="container">
          <div class="nav-pill">
            <a class="navbar-brand" href="<?= esc(site_url('/'), 'attr') ?>#top">
                <?php if ($brandLogo !== null): ?>
                    <picture>
                        <?php if ($brandLogo['webp'] !== null): ?>
                            <source srcset="<?= esc(base_url($brandLogo['webp']), 'attr') ?>" type="image/webp">
                        <?php endif; ?>
                        <img class="brand-logo" src="<?= esc(base_url($brandLogo['png']), 'attr') ?>" alt="<?= esc($L['brand'], 'attr') ?>"
                             width="<?= (int) $brandLogo['width'] ?>" height="<?= (int) $brandLogo['height'] ?>">
                    </picture>
                <?php else: ?>
                    <span class="brand-mark" aria-hidden="true"><i class="bi bi-compass"></i></span>
                    <span class="brand-text"><?= esc($L['brand']) ?></span>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#site-nav"
                    aria-controls="site-nav" aria-expanded="false" aria-label="<?= esc($L['toggle'], 'attr') ?>">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="site-nav">
                <ul class="navbar-nav ms-auto me-lg-2 mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= esc($navBase . '#about', 'attr') ?>"><?= esc($L['about']) ?></a></li>
                    <li class="nav-item"><a class="<?= $navClass('fleet') ?>" href="<?= esc($navBase . '#fleet', 'attr') ?>"><?= esc($L['fleet']) ?></a></li>
                    <li class="nav-item"><a class="<?= $navClass('gallery') ?>" href="<?= esc($navBase . '#gallery', 'attr') ?>"><?= esc($L['gallery']) ?></a></li>
                    <li class="nav-item"><a class="<?= $navClass('news') ?>" href="<?= esc($navBase . '#news', 'attr') ?>"><?= esc($L['news']) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= esc($navBase . '#faq', 'attr') ?>"><?= esc($L['faq']) ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= esc($navBase . '#contact', 'attr') ?>"><?= esc($L['contact']) ?></a></li>
                </ul>
                <div class="dropdown">
                    <button class="btn btn-lang dropdown-toggle" type="button" id="langDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-translate" aria-hidden="true"></i>
                        <span class="visually-hidden"><?= esc($L['langLabel']) ?>:</span>
                        <?= esc($langNames[$layoutLang]) ?>
                    </button>
                    <ul class="lang-selector dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <?php foreach ($langNames as $code => $name): ?>
                            <li>
                                <a class="dropdown-item<?= $code === $layoutLang ? ' active' : '' ?>"
                                   href="<?= esc(site_url('lang/' . $code) . $langReturn, 'attr') ?>"
                                   hreflang="<?= esc($code, 'attr') ?>" lang="<?= esc($code, 'attr') ?>"<?= $code === $layoutLang ? ' aria-current="true"' : '' ?>><?= esc($name) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
          </div>
        </div>
    </nav>
</header>

<main id="main">
    <?= $this->renderSection('content') ?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-card">
            <span class="footer-copy">&copy; <?= esc(date('Y')) ?> <?= esc($L['brand']) ?>. <?= esc($L['footer']) ?></span>
            <a class="footer-top" href="#top"><i class="bi bi-arrow-up-circle" aria-hidden="true"></i> <?= esc($L['top']) ?></a>
        </div>
    </div>
</footer>

<?php if ($floatingWa !== null): ?>
    <a class="wa-float" href="<?= esc($floatingWa, 'attr') ?>" target="_blank" rel="noopener"
       aria-label="<?= esc($L['wa'], 'attr') ?>">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
    </a>
<?php endif; ?>

<div class="modal fade lightbox" id="lightbox" tabindex="-1" aria-label="<?= esc($L['lbLabel'], 'attr') ?>" aria-hidden="true">
    <button type="button" class="lightbox__close" data-bs-dismiss="modal" aria-label="<?= esc($L['lbClose'], 'attr') ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    <button type="button" class="lightbox__nav lightbox__nav--prev" data-lightbox-prev aria-label="<?= esc($L['lbPrev'], 'attr') ?>" hidden><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
    <button type="button" class="lightbox__nav lightbox__nav--next" data-lightbox-next aria-label="<?= esc($L['lbNext'], 'attr') ?>" hidden><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content lightbox__content">
            <figure class="lightbox__figure">
                <img class="lightbox__img" alt="" src="">
                <figcaption class="lightbox__caption" hidden></figcaption>
            </figure>
        </div>
    </div>
</div>

<script src="<?= esc(base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'), 'attr') ?>"></script>
<script src="<?= esc(base_url('assets/js/landing.js'), 'attr') ?>" defer></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
