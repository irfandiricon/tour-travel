<?php
/**
 * Public one-page landing view (Videmy-style redesign).
 *
 * @var string $lang
 * @var array  $languages
 * @var array  $hero
 * @var string $about
 * @var string $galleryTitle
 * @var array  $contact
 * @var array  $fleet
 * @var array  $news
 * @var array  $faq
 * @var array  $gallery
 */

helper('ui');

$labelsByLang = [
    'en' => [
        'brand' => 'Kimigo Travel', 'skip' => 'Skip to content', 'menu' => 'Menu', 'navLabel' => 'Main navigation',
        'langLabel' => 'Language', 'about' => 'About Us', 'fleet' => 'Our Fleet', 'gallery' => 'Gallery',
        'news' => 'News & Information', 'faq' => 'FAQ', 'contact' => 'Contact',
        'book' => 'Book via WhatsApp', 'chat' => 'Chat on WhatsApp', 'email' => 'Email', 'address' => 'Address',
        'readyHero' => 'Welcome', 'imageAlt' => 'Vehicle image',
        'emptyAbout' => 'About information is currently unavailable.', 'emptyFleet' => 'No vehicles are available at the moment.',
        'emptyGallery' => 'The gallery is currently empty.', 'emptyNews' => 'No news or information to show yet.',
        'emptyFaq' => 'No frequently asked questions yet.',
        'emptyContact' => 'Contact details are currently unavailable.', 'footer' => 'All rights reserved.',
        'viewFleet' => 'View Fleet', 'viewMore' => 'View more', 'readMore' => 'Read more', 'zoomImage' => 'View larger image', 'readyToBook' => 'Ready to book', 'trust' => '{n} vehicles ready to go',
        'pause' => 'Pause slideshow', 'play' => 'Play slideshow',
        'statFleet' => 'Vehicles in our fleet', 'statGallery' => 'Moments captured', 'statNews' => 'News & updates',
        'statFaq' => 'Questions answered',
        'testimonials' => 'What Our Customers Say', 'ratingSummary' => '{avg} out of 5 from {n} reviews', 'ratingSummaryOne' => '{avg} out of 5 from 1 review', 'rated' => 'Rated {n} out of 5', 'reviewsPause' => 'Pause reviews', 'reviewsPlay' => 'Play reviews',
        'moreFleetSubtitle' => 'Pick the ride that fits your trip',
    ],
    'id' => [
        'brand' => 'Kimigo Travel', 'skip' => 'Lewati ke konten', 'menu' => 'Menu', 'navLabel' => 'Navigasi utama',
        'langLabel' => 'Bahasa', 'about' => 'Tentang Kami', 'fleet' => 'Armada Kami', 'gallery' => 'Galeri',
        'news' => 'Berita & Informasi', 'faq' => 'FAQ', 'contact' => 'Kontak',
        'book' => 'Pesan via WhatsApp', 'chat' => 'Chat via WhatsApp', 'email' => 'Email', 'address' => 'Alamat',
        'readyHero' => 'Selamat Datang', 'imageAlt' => 'Gambar kendaraan',
        'emptyAbout' => 'Informasi tentang kami belum tersedia.', 'emptyFleet' => 'Belum ada kendaraan yang tersedia saat ini.',
        'emptyGallery' => 'Galeri masih kosong.', 'emptyNews' => 'Belum ada berita atau informasi.',
        'emptyFaq' => 'Belum ada pertanyaan yang sering diajukan.',
        'emptyContact' => 'Detail kontak belum tersedia.', 'footer' => 'Hak cipta dilindungi.',
        'viewFleet' => 'Lihat Armada', 'viewMore' => 'Lihat Selengkapnya', 'readMore' => 'Baca selengkapnya', 'zoomImage' => 'Perbesar gambar', 'readyToBook' => 'Siap dipesan', 'trust' => '{n} armada siap jalan',
        'pause' => 'Jeda slideshow', 'play' => 'Putar slideshow',
        'statFleet' => 'Armada kami', 'statGallery' => 'Momen terabadi', 'statNews' => 'Berita & kabar',
        'statFaq' => 'Pertanyaan terjawab',
        'testimonials' => 'Kata Pelanggan Kami', 'ratingSummary' => '{avg} dari 5 berdasarkan {n} ulasan', 'ratingSummaryOne' => '{avg} dari 5 berdasarkan 1 ulasan', 'rated' => 'Rating {n} dari 5', 'reviewsPause' => 'Jeda ulasan', 'reviewsPlay' => 'Putar ulasan',
        'moreFleetSubtitle' => 'Pilih kendaraan yang pas untuk perjalananmu',
    ],
    'ms' => [
        'brand' => 'Kimigo Travel', 'skip' => 'Langkau ke kandungan', 'menu' => 'Menu', 'navLabel' => 'Navigasi utama',
        'langLabel' => 'Bahasa', 'about' => 'Tentang Kami', 'fleet' => 'Armada Kami', 'gallery' => 'Galeri',
        'news' => 'Berita & Maklumat', 'faq' => 'Soalan Lazim', 'contact' => 'Hubungi Kami',
        'book' => 'Tempah melalui WhatsApp', 'chat' => 'Sembang di WhatsApp', 'email' => 'E-mel', 'address' => 'Alamat',
        'readyHero' => 'Selamat Datang', 'imageAlt' => 'Gambar kenderaan',
        'emptyAbout' => 'Maklumat tentang kami belum tersedia.', 'emptyFleet' => 'Tiada kenderaan tersedia buat masa ini.',
        'emptyGallery' => 'Galeri masih kosong.', 'emptyNews' => 'Belum ada berita atau maklumat.',
        'emptyFaq' => 'Belum ada soalan lazim.',
        'emptyContact' => 'Butiran perhubungan belum tersedia.', 'footer' => 'Hak cipta terpelihara.',
        'viewFleet' => 'Lihat Armada', 'viewMore' => 'Lihat Lagi', 'readMore' => 'Baca lagi', 'zoomImage' => 'Besarkan imej', 'readyToBook' => 'Sedia ditempah', 'trust' => '{n} kenderaan sedia bergerak',
        'pause' => 'Jeda tayangan', 'play' => 'Main tayangan',
        'statFleet' => 'Kenderaan dalam armada', 'statGallery' => 'Detik diabadikan', 'statNews' => 'Berita & kemas kini',
        'statFaq' => 'Soalan dijawab',
        'testimonials' => 'Kata Pelanggan Kami', 'ratingSummary' => '{avg} daripada 5 berdasarkan {n} ulasan', 'ratingSummaryOne' => '{avg} daripada 5 berdasarkan 1 ulasan', 'rated' => 'Penilaian {n} daripada 5', 'reviewsPause' => 'Jeda ulasan', 'reviewsPlay' => 'Main ulasan',
        'moreFleetSubtitle' => 'Pilih kenderaan yang sesuai untuk perjalanan anda',
    ],
];
$langNames = ['en' => 'English', 'id' => 'Indonesia', 'ms' => 'Melayu'];

$lang = isset($labelsByLang[$lang ?? '']) ? $lang : 'en';
$t    = $labelsByLang[$lang];

$hero    = is_array($hero ?? null) ? $hero : [];
$contact = is_array($contact ?? null) ? $contact : [];
$fleet   = is_array($fleet ?? null) ? $fleet : [];
$news    = is_array($news ?? null) ? $news : [];
$faq     = is_array($faq ?? null) ? $faq : [];
$gallery = is_array($gallery ?? null) ? $gallery : [];
$testimonials  = is_array($testimonials ?? null) ? array_values($testimonials) : [];
$ratingSummary = is_array($ratingSummary ?? null) && (int) ($ratingSummary['count'] ?? 0) > 0 ? $ratingSummary : null;
$about   = (string) ($about ?? '');
$aboutTitle = trim((string) ($aboutTitle ?? ''));
$aboutImage = trim((string) ($aboutImage ?? ''));
$aboutImageWide = (bool) ($aboutImageWide ?? false);

$heroTitle    = trim((string) ($hero['title'] ?? ''));
$heroSubtitle = trim((string) ($hero['subtitle'] ?? ''));
$galleryHeading = trim((string) ($galleryTitle ?? '')) !== '' ? (string) $galleryTitle : $t['gallery'];

$imgUrl = static function (string $path): string {
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return base_url(ltrim($path, '/'));
};
$placeholder = $imgUrl('assets/img/fleet-placeholder.svg');
$contactWa   = $contact['whatsapp'] ?? null;
$contactEmail   = trim((string) ($contact['email'] ?? ''));
$contactAddress = trim((string) ($contact['address'] ?? ''));
$contactBody    = trim((string) ($contact['body'] ?? ''));
$hasContact = $contactBody !== '' || $contactEmail !== '' || $contactAddress !== '' || $contactWa !== null;

$carouselLabels = [
    'en' => ['prev' => 'Previous', 'next' => 'Next', 'slide' => 'Go to slide'],
    'id' => ['prev' => 'Sebelumnya', 'next' => 'Berikutnya', 'slide' => 'Ke slide'],
    'ms' => ['prev' => 'Sebelumnya', 'next' => 'Seterusnya', 'slide' => 'Ke slaid'],
][$lang];

$heroSlides = is_array($heroSlides ?? null) ? array_values($heroSlides) : [];
$heroSlides = array_slice($heroSlides, 0, 5);
$slideCount = count($heroSlides);
$trackUrl   = (string) ($trackBookingUrl ?? '');

// The lists above are previews; real totals and page URLs come from the controller.
$totals = is_array($totals ?? null) ? $totals : [];
$totalOf = static fn (string $key, array $shown): int => max(count($shown), (int) ($totals[$key] ?? 0));
$pages = is_array($pages ?? null) ? $pages : [];
foreach (['fleet', 'gallery', 'news'] as $pageKey) {
    $pages[$pageKey] = (string) ($pages[$pageKey] ?? site_url($pageKey));
}
$viewMore = static function (string $key) use ($pages, $t): string {
    return '<div class="section-more"><a class="btn-more" href="' . esc($pages[$key], 'attr') . '">'
        . esc($t['viewMore']) . ' <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>';
};

$fleetCount = $totalOf('fleet', $fleet);
$stats = ui_landing_stats(
    [
        'fleet'       => $fleetCount,
        'gallery'     => $totalOf('gallery', $gallery),
        'news'        => $totalOf('news', $news),
        'faq'         => count($faq),
    ],
    [
        'fleet'       => $t['statFleet'],
        'gallery'     => $t['statGallery'],
        'news'        => $t['statNews'],
        'faq'         => $t['statFaq'],
    ]
);

$brandName = trim((string) ($brand ?? '')) !== '' ? trim((string) $brand) : $t['brand'];
$this->extend('layouts/public');
$this->setVar('brand', $brandName);
// Full SEO metadata is built by LandingController; fall back to a minimal set when absent.
if (! is_array($seo ?? null) || $seo === []) {
    $this->setVar('seo', [
        'title'       => $heroTitle !== '' ? $heroTitle : $brandName,
        'description' => $heroSubtitle !== '' ? $heroSubtitle : $brandName,
        'robots'      => 'index, follow',
    ]);
}

$heroHeading = $heroTitle !== '' ? $heroTitle : $t['readyHero'];
?>

<?= $this->section('content') ?>
<section id="top" class="hero-section" aria-labelledby="hero-title">
    <div class="container">
        <div class="hero-intro">
            <h1 id="hero-title" class="hero-title"><?= ui_highlight_last_word($heroHeading) ?></h1>
            <?php if ($heroSubtitle !== ''): ?>
                <p class="hero-subtitle"><?= esc($heroSubtitle) ?></p>
            <?php endif; ?>
            <div class="hero-actions">
                <?php if ($contactWa !== null): ?>
                    <a class="btn btn-wa btn-success btn-lg" href="<?= esc($contactWa, 'attr') ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i> <?= esc($t['chat']) ?></a>
                <?php endif; ?>
                <a class="btn-lime" href="#fleet"><?= esc($t['viewFleet']) ?></a>
                <?php if ($slideCount > 1): ?>
                    <button type="button" class="btn-lime" data-hero-toggle aria-pressed="false"
                            data-label-pause="<?= esc($t['pause'], 'attr') ?>" data-label-play="<?= esc($t['play'], 'attr') ?>"
                            aria-label="<?= esc($t['pause'], 'attr') ?>"><i class="bi bi-pause-fill" aria-hidden="true"></i></button>
                <?php endif; ?>
            </div>
            <?php if ($fleetCount > 0): ?>
                <p class="trust-pill"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?= esc(str_replace('{n}', (string) $fleetCount, $t['trust'])) ?></p>
            <?php endif; ?>
        </div>

        <div class="hero-media">
            <div class="browser-frame">
                <div class="browser-frame__bar" aria-hidden="true"><span></span><span></span><span></span></div>
                <div class="browser-frame__body">
                    <?php if ($slideCount === 0): ?>
                        <div class="hero"></div>
                    <?php else: ?>
                        <div id="hero-carousel" class="hero-carousel carousel slide<?= $slideCount > 1 ? ' carousel-fade' : '' ?>"
                             data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover" data-bs-keyboard="true"
                             aria-roledescription="carousel" aria-label="<?= esc($heroHeading, 'attr') ?>">
                            <?php if ($slideCount > 1): ?>
                                <div class="carousel-indicators">
                                    <?php foreach ($heroSlides as $i => $slide): ?>
                                        <button type="button" data-bs-target="#hero-carousel" data-bs-slide-to="<?= (int) $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?>
                                                aria-label="<?= esc($carouselLabels['slide'] . ' ' . ($i + 1), 'attr') ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="carousel-inner">
                                <?php foreach ($heroSlides as $i => $slide): ?>
                                    <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                                        <img src="<?= esc((string) ($slide['image'] ?? ''), 'attr') ?>" alt="<?= esc((string) ($slide['alt'] ?? ''), 'attr') ?>"<?= $i === 0 ? '' : ' loading="lazy"' ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($slideCount > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#hero-carousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden"><?= esc($carouselLabels['prev']) ?></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#hero-carousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden"><?= esc($carouselLabels['next']) ?></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($stats !== []): ?>
            <div class="stats-row">
                <?php foreach ($stats as $s): ?>
                    <div class="stat-card<?= $s['dark'] ? ' stat-card--dark' : '' ?>">
                        <div class="stat-card__value"><?= esc(number_format($s['value'])) ?></div>
                        <div class="stat-card__label"><?= esc($s['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Heading: the CMS "About heading" when set, otherwise the site name. Paragraphs are split on blank lines.
$aboutHeading    = $aboutTitle !== '' ? $aboutTitle : $brandName;
$aboutParagraphs = array_values(array_filter(
    array_map('trim', preg_split('/\R{2,}/', trim($about)) ?: []),
    static fn (string $para): bool => $para !== ''
));
?>
<section id="about" class="section about-section section--white" aria-labelledby="about-title">
    <div class="container">
        <div class="about-grid<?= $aboutImage === '' ? ' about-grid--single' : ($aboutImageWide ? ' about-grid--wide' : '') ?>">
            <?php if ($aboutImage !== ''): ?>
                <figure class="about-media">
                    <button type="button" class="zoomable" data-lightbox
                            data-lightbox-src="<?= esc($imgUrl($aboutImage), 'attr') ?>" data-lightbox-caption="<?= esc($aboutHeading, 'attr') ?>"
                            aria-label="<?= esc($t['zoomImage'] . ': ' . $aboutHeading, 'attr') ?>">
                        <img src="<?= esc($imgUrl($aboutImage), 'attr') ?>" alt="<?= esc($aboutHeading, 'attr') ?>" loading="lazy">
                        <i class="bi bi-zoom-in zoom-icon" aria-hidden="true"></i>
                    </button>
                </figure>
            <?php endif; ?>
            <div class="about-content">
                <p class="about-eyebrow"><?= esc($t['about']) ?></p>
                <h2 id="about-title" class="about-title"><?= ui_highlight_last_word($aboutHeading) ?></h2>
                <?php if ($aboutParagraphs !== []): ?>
                    <?php foreach ($aboutParagraphs as $para): ?>
                        <p class="about-text"><?= esc($para) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state"><?= esc($t['emptyAbout']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<section id="fleet" class="section section--sky" aria-labelledby="fleet-title">
    <div class="container">
        <div class="section-head">
            <h2 id="fleet-title"><?= esc($t['fleet']) ?></h2>
            <p class="section-sub"><?= esc($t['moreFleetSubtitle']) ?></p>
        </div>
        <?php if ($fleet === []): ?>
            <p class="empty-state"><?= esc($t['emptyFleet']) ?></p>
        <?php else: ?>
            <div class="fleet-rail" tabindex="0" role="group" aria-label="<?= esc($t['fleet'], 'attr') ?>">
                <?php foreach ($fleet as $item): ?>
                    <?= ui_partial('fleet_card', ['item' => $item, 't' => $t, 'imgUrl' => $imgUrl, 'placeholder' => $placeholder, 'trackUrl' => $trackUrl]) ?>
                <?php endforeach; ?>
            </div>
            <?= $viewMore('fleet') ?>
        <?php endif; ?>
    </div>
</section>

<section id="gallery" class="section section--sand" aria-labelledby="gallery-title">
    <div class="container">
        <div class="section-card">
            <div class="section-head">
                <h2 id="gallery-title"><?= esc($galleryHeading) ?></h2>
            </div>
            <?php if ($gallery === []): ?>
                <p class="empty-state"><?= esc($t['emptyGallery']) ?></p>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php foreach ($gallery as $g): ?>
                        <?= ui_partial('gallery_item', ['g' => $g, 't' => $t, 'imgUrl' => $imgUrl, 'placeholder' => $placeholder]) ?>
                    <?php endforeach; ?>
                </div>
                <?= $viewMore('gallery') ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="news" class="section section--mist" aria-labelledby="news-title">
    <div class="container">
        <div class="section-head">
            <h2 id="news-title"><?= esc($t['news']) ?></h2>
        </div>
        <?php if ($news === []): ?>
            <p class="empty-state"><?= esc($t['emptyNews']) ?></p>
        <?php else: ?>
            <div class="news-carousel" data-news-carousel>
                <button type="button" class="news-carousel__btn news-carousel__btn--prev" data-news-prev
                        aria-controls="news-rail" aria-label="<?= esc($carouselLabels['prev'], 'attr') ?>">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <div id="news-rail" class="news-rail" tabindex="0" role="group" aria-label="<?= esc($t['news'], 'attr') ?>">
                    <?php foreach ($news as $n): ?>
                        <?= ui_partial('news_card', ['n' => $n, 't' => $t, 'imgUrl' => $imgUrl]) ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="news-carousel__btn news-carousel__btn--next" data-news-next
                        aria-controls="news-rail" aria-label="<?= esc($carouselLabels['next'], 'attr') ?>">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            <?= $viewMore('news') ?>
        <?php endif; ?>
    </div>
</section>

<?php if ($testimonials !== []): ?>
<section id="testimonials" class="section section--peach" aria-labelledby="testimonials-title">
    <div class="container">
        <div class="section-head">
            <h2 id="testimonials-title"><?= esc($t['testimonials']) ?></h2>
            <?php if ($ratingSummary !== null): ?>
                <?php
                $avg     = number_format((float) $ratingSummary['average'], 1, $lang === 'en' ? '.' : ',', '');
                $count   = (int) $ratingSummary['count'];
                $summary = str_replace(['{avg}', '{n}'], [$avg, (string) $count], $count === 1 ? $t['ratingSummaryOne'] : $t['ratingSummary']);
                ?>
                <p class="section-sub testi-summary"><i class="bi bi-star-fill" aria-hidden="true"></i> <?= esc($summary) ?></p>
            <?php endif; ?>
        </div>
        <div class="news-carousel news-carousel--testi" data-news-carousel data-loop data-autoplay="5000">
            <button type="button" class="news-carousel__btn news-carousel__btn--prev" data-news-prev
                    aria-controls="testimonials-rail" aria-label="<?= esc($carouselLabels['prev'], 'attr') ?>">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <div id="testimonials-rail" class="news-rail" tabindex="0" role="group" aria-label="<?= esc($t['testimonials'], 'attr') ?>">
                <?php foreach ($testimonials as $r): ?>
                    <?= ui_partial('testimonial_card', ['r' => $r, 't' => $t, 'imgUrl' => $imgUrl]) ?>
                <?php endforeach; ?>
            </div>
            <button type="button" class="news-carousel__btn news-carousel__btn--next" data-news-next
                    aria-controls="testimonials-rail" aria-label="<?= esc($carouselLabels['next'], 'attr') ?>">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
        <div class="testi-controls">
            <button type="button" class="carousel-toggle" data-carousel-toggle aria-pressed="false" aria-controls="testimonials-rail"
                    data-label-pause="<?= esc($t['reviewsPause'], 'attr') ?>" data-label-play="<?= esc($t['reviewsPlay'], 'attr') ?>"
                    aria-label="<?= esc($t['reviewsPause'], 'attr') ?>" title="<?= esc($t['reviewsPause'], 'attr') ?>">
                <i class="bi bi-pause-fill" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>
<section id="faq" class="section section--mint" aria-labelledby="faq-title">
    <div class="container">
        <div class="section-head">
            <h2 id="faq-title"><?= esc($t['faq']) ?></h2>
        </div>
        <?php if ($faq === []): ?>
            <p class="empty-state"><?= esc($t['emptyFaq']) ?></p>
        <?php else: ?>
            <div class="accordion" id="faq-accordion">
                <?php foreach ($faq as $i => $f): ?>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button type="button" class="accordion-button collapsed faq-question" id="faq-q-<?= (int) $i ?>"
                                    data-bs-toggle="collapse" data-bs-target="#faq-a-<?= (int) $i ?>"
                                    aria-expanded="false" aria-controls="faq-a-<?= (int) $i ?>"><?= esc((string) ($f['question'] ?? '')) ?></button>
                        </h3>
                        <div id="faq-a-<?= (int) $i ?>" class="accordion-collapse collapse" role="region"
                             aria-labelledby="faq-q-<?= (int) $i ?>" data-bs-parent="#faq-accordion">
                            <div class="accordion-body faq-answer"><?= esc((string) ($f['answer'] ?? '')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="contact" class="section section--sky-deep" aria-labelledby="contact-title">
    <div class="container">
        <div class="contact-card">
            <h2 id="contact-title"><?= esc($t['contact']) ?></h2>
            <?php if (! $hasContact): ?>
                <p class="empty-state"><?= esc($t['emptyContact']) ?></p>
            <?php else: ?>
                <div class="contact-box">
                    <?php if ($contactBody !== ''): ?><p><?= esc($contactBody) ?></p><?php endif; ?>
                    <?php if ($contactEmail !== ''): ?>
                        <p><i class="bi bi-envelope" aria-hidden="true"></i> <strong><?= esc($t['email']) ?>:</strong> <a href="mailto:<?= esc($contactEmail, 'url') ?>"><?= esc($contactEmail) ?></a></p>
                    <?php endif; ?>
                    <?php if ($contactAddress !== ''): ?>
                        <p><i class="bi bi-geo-alt" aria-hidden="true"></i> <strong><?= esc($t['address']) ?>:</strong> <?= esc($contactAddress) ?></p>
                    <?php endif; ?>
                    <?php if ($contactWa !== null): ?>
                        <p><a class="btn-lime btn-wa" href="<?= esc($contactWa, 'attr') ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i> <?= esc($t['chat']) ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
