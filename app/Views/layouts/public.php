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

// Booking form opened by the fleet "Book via WhatsApp" buttons (see landing.js initBookingForm).
// `msg` builds the WhatsApp text; {brand} and {fleet} are filled in by the script.
$bookingLabels = [
    'en' => [
        'eyebrow' => 'Booking form', 'name' => 'Customer name', 'namePh' => 'e.g. John Smith', 'date' => 'Travel date',
        'pax' => 'Number of passengers', 'paxPh' => 'e.g. 4', 'pickup' => 'Pickup location', 'pickupPh' => 'e.g. Hang Nadim Airport / Marriott Hotel',
        'priceFrom' => 'Price starting from', 'priceNote' => 'The final price is confirmed on WhatsApp.', 'submit' => 'Book via WhatsApp',
        'hint' => 'WhatsApp opens with your booking details filled in.', 'close' => 'Close', 'required' => 'required',
        'errRequired' => 'Please fill in this field.', 'errPax' => 'Enter a whole number from 1 to 60.', 'errDate' => 'Choose today or a later date.',
        'msg' => ['intro' => 'Hello {brand}, I would like to book the {fleet}:', 'name' => 'Name', 'date' => 'Travel date', 'pax' => 'Passengers',
                  'paxUnit' => 'people', 'pickup' => 'Pickup location', 'price' => 'Price starting from', 'outro' => 'Please confirm the vehicle availability. Thank you!'],
        'months' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    ],
    'id' => [
        'eyebrow' => 'Form pemesanan', 'name' => 'Nama Pemesan', 'namePh' => 'Contoh: Budi Santoso', 'date' => 'Tanggal Perjalanan',
        'pax' => 'Jumlah Penumpang', 'paxPh' => 'Contoh: 4', 'pickup' => 'Lokasi Penjemputan', 'pickupPh' => 'Contoh: Bandara Hang Nadim / Hotel Marriott',
        'priceFrom' => 'Harga dimulai dari', 'priceNote' => 'Harga akhir dikonfirmasi lewat WhatsApp.', 'submit' => 'Pesan via WhatsApp',
        'hint' => 'WhatsApp akan terbuka dengan detail pesanan Anda.', 'close' => 'Tutup', 'required' => 'wajib',
        'errRequired' => 'Wajib diisi.', 'errPax' => 'Masukkan angka bulat 1 sampai 60.', 'errDate' => 'Pilih hari ini atau tanggal setelahnya.',
        'msg' => ['intro' => 'Halo {brand}, saya mau booking unit {fleet}:', 'name' => 'Nama', 'date' => 'Tanggal Perjalanan', 'pax' => 'Jumlah Penumpang',
                  'paxUnit' => 'orang', 'pickup' => 'Lokasi Penjemputan', 'price' => 'Harga dimulai dari', 'outro' => 'Mohon konfirmasi ketersediaan unit. Terima kasih!'],
        'months' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
    ],
    'ms' => [
        'eyebrow' => 'Borang tempahan', 'name' => 'Nama Penempah', 'namePh' => 'Contoh: Ahmad Faiz', 'date' => 'Tarikh Perjalanan',
        'pax' => 'Bilangan Penumpang', 'paxPh' => 'Contoh: 4', 'pickup' => 'Lokasi Ambilan', 'pickupPh' => 'Contoh: Lapangan Terbang Hang Nadim / Hotel Marriott',
        'priceFrom' => 'Harga bermula dari', 'priceNote' => 'Harga akhir disahkan melalui WhatsApp.', 'submit' => 'Tempah melalui WhatsApp',
        'hint' => 'WhatsApp akan dibuka dengan butiran tempahan anda.', 'close' => 'Tutup', 'required' => 'wajib',
        'errRequired' => 'Sila isi ruangan ini.', 'errPax' => 'Masukkan nombor bulat 1 hingga 60.', 'errDate' => 'Pilih hari ini atau tarikh selepasnya.',
        'msg' => ['intro' => 'Hai {brand}, saya ingin menempah {fleet}:', 'name' => 'Nama', 'date' => 'Tarikh Perjalanan', 'pax' => 'Bilangan Penumpang',
                  'paxUnit' => 'orang', 'pickup' => 'Lokasi Ambilan', 'price' => 'Harga bermula dari', 'outro' => 'Mohon sahkan ketersediaan kenderaan. Terima kasih!'],
        'months' => ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'],
    ],
];
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

<?php if ($floatingWa !== null): // booking buttons only exist when a WhatsApp number is set ?>
<?php $B = $bookingLabels[$layoutLang] ?? $bookingLabels['en']; $req = '<span class="req" aria-hidden="true">*</span><span class="visually-hidden"> (' . esc($B['required']) . ')</span>'; ?>
<div class="modal fade booking-modal" id="booking-modal" tabindex="-1" aria-labelledby="booking-fleet" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content booking-form" data-booking-form novalidate autocomplete="on">
            <div class="modal-header">
                <div>
                    <p class="booking-eyebrow"><?= esc($B['eyebrow']) ?></p>
                    <h2 class="modal-title h5" id="booking-fleet" data-booking-fleet></h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= esc($B['close'], 'attr') ?>"></button>
            </div>
            <div class="modal-body">
                <div class="booking-field">
                    <label for="booking-name" class="booking-label"><?= esc($B['name']) ?> <?= $req ?></label>
                    <input type="text" class="form-control" id="booking-name" name="name" maxlength="100" autocomplete="name" required
                           placeholder="<?= esc($B['namePh'], 'attr') ?>" aria-describedby="booking-name-error">
                    <div class="invalid-feedback" id="booking-name-error"></div>
                </div>
                <div class="booking-field">
                    <label for="booking-date" class="booking-label"><?= esc($B['date']) ?> <?= $req ?></label>
                    <input type="date" class="form-control" id="booking-date" name="date" required aria-describedby="booking-date-error">
                    <div class="invalid-feedback" id="booking-date-error"></div>
                </div>
                <div class="booking-field">
                    <label for="booking-pax" class="booking-label"><?= esc($B['pax']) ?> <?= $req ?></label>
                    <input type="number" class="form-control" id="booking-pax" name="pax" min="1" max="60" step="1" inputmode="numeric" required
                           placeholder="<?= esc($B['paxPh'], 'attr') ?>" aria-describedby="booking-pax-error">
                    <div class="invalid-feedback" id="booking-pax-error"></div>
                </div>
                <div class="booking-field">
                    <label for="booking-pickup" class="booking-label"><?= esc($B['pickup']) ?> <?= $req ?></label>
                    <input type="text" class="form-control" id="booking-pickup" name="pickup" maxlength="150" autocomplete="street-address" required
                           placeholder="<?= esc($B['pickupPh'], 'attr') ?>" aria-describedby="booking-pickup-error">
                    <div class="invalid-feedback" id="booking-pickup-error"></div>
                </div>
                <div class="booking-price" data-booking-price-box hidden>
                    <span class="booking-price__label"><?= esc($B['priceFrom']) ?></span>
                    <strong class="booking-price__value" data-booking-price></strong>
                    <small class="booking-price__note"><?= esc($B['priceNote']) ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-wa btn-success booking-submit w-100"><i class="bi bi-whatsapp" aria-hidden="true"></i> <?= esc($B['submit']) ?></button>
                <p class="booking-hint"><?= esc($B['hint']) ?></p>
            </div>
        </form>
    </div>
</div>
<script type="application/json" id="booking-i18n"><?= json_encode(['brand' => $L['brand']] + $B, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>

<script src="<?= esc(base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'), 'attr') ?>"></script>
<script src="<?= esc(base_url('assets/js/landing.js'), 'attr') ?>" defer></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
