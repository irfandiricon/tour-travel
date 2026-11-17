<?php

declare(strict_types=1);

/**
 * UI helper: fungsi murni untuk view (tanpa akses DB, sesi, atau jaringan).
 * Muat dengan helper('ui').
 */

if (! function_exists('ui_landing_stats')) {
    /**
     * Susun kartu statistik landing dari hitungan data nyata.
     *
     * @param array<string,mixed>  $counts kunci: fleet|gallery|news|faq
     * @param array<string,string> $labels label per kunci (sudah per bahasa)
     *
     * @return list<array{key:string,value:int,label:string,dark:bool}>
     */
    function ui_landing_stats(array $counts, array $labels): array
    {
        $priority = ['fleet', 'gallery', 'news', 'faq'];
        $result   = [];

        foreach ($priority as $key) {
            if (count($result) >= 3) {
                break;
            }

            $raw   = $counts[$key] ?? 0;
            $value = max(0, is_numeric($raw) ? (int) $raw : 0);

            if ($value > 0 && isset($labels[$key]) && is_string($labels[$key])) {
                $result[] = [
                    'key'   => $key,
                    'value' => $value,
                    'label' => $labels[$key],
                    'dark'  => $result === [],
                ];
            }
        }

        return $result;
    }
}

if (! function_exists('ui_highlight_last_word')) {
    /**
     * Escape teks lalu bungkus kata terakhir dengan <mark class="hl">.
     * Mengembalikan HTML aman; string kosong bila input hanya spasi.
     */
    function ui_highlight_last_word(string $text): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }

        // Pemisahan berbasis byte pada spasi ASCII agar aman untuk input apa pun.
        if (preg_match('/^(.*\s)?(\S+)$/s', $trimmed, $m) !== 1) {
            return esc($trimmed);
        }

        $head = rtrim($m[1] ?? '');
        $last = '<mark class="hl">' . esc($m[2]) . '</mark>';

        return $head === '' ? $last : esc($head) . ' ' . $last;
    }
}

if (! function_exists('ui_initials')) {
    /**
     * 1-2 huruf kapital dari kata-kata pertama nama; fallback "A". Tanpa markup.
     */
    function ui_initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false) {
            return 'A';
        }

        $initials = '';
        foreach ($words as $word) {
            if (preg_match('/\p{L}/u', $word, $m) === 1) {
                $initials .= $m[0];
                if (mb_strlen($initials, 'UTF-8') >= 2) {
                    break;
                }
            }
        }

        if ($initials === '') {
            return 'A';
        }

        return mb_substr(mb_strtoupper($initials, 'UTF-8'), 0, 2, 'UTF-8');
    }
}

if (! function_exists('ui_nav_is_active')) {
    /**
     * Dashboard cocok persis; item lain cocok persis atau berawalan "item/".
     */
    function ui_nav_is_active(string $requestPath, string $itemPath): bool
    {
        $r = trim($requestPath, '/');
        $i = trim($itemPath, '/');

        if ($i === 'dashboard') {
            return $r === 'dashboard';
        }

        return $r === $i || str_starts_with($r, $i . '/');
    }
}

if (! function_exists('ui_admin_date')) {
    /**
     * Tanggal header admin dalam bahasa Inggris, mis. "Monday, 12 May 2025".
     */
    function ui_admin_date(\DateTimeInterface $now): string
    {
        return $now->format('l, d F Y');
    }
}


if (! function_exists('ui_favicon_tags')) {
    /**
     * <link> tags for public/favicon.ico. The ?v= query is the file mtime, so browsers
     * (which cache favicons aggressively) fetch the new icon as soon as the file changes.
     */
    function ui_favicon_tags(): string
    {
        $file = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'favicon.ico';
        if (! is_file($file)) {
            return '';
        }
        $href = esc(base_url('favicon.ico') . '?v=' . (int) @filemtime($file), 'attr');

        return '<link rel="icon" href="' . $href . '" sizes="any" type="image/x-icon">' . "\n"
            . '    <link rel="shortcut icon" href="' . $href . '" type="image/x-icon">';
    }
}


if (! function_exists('ui_partial')) {
    /**
     * Render app/Views/partials/public/{name}.php with the given variables and return the HTML.
     * Uses a plain include (not the view renderer) so it is safe inside layout sections and
     * cannot disturb the renderer's section/layout state or its setVar() data.
     *
     * @param array<string, mixed> $vars
     */
    function ui_partial(string $name, array $vars = []): string
    {
        $file = APPPATH . 'Views/partials/public/' . $name . '.php';

        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            include $__file;

            return (string) ob_get_clean();
        })($file, $vars);
    }
}

if (! function_exists('ui_page_labels')) {
    /**
     * Labels for the Fleet / Gallery / News & Information pages in en, id and ms.
     * `pages.{kind}` has title, sub (heading subtitle), desc (meta description) and empty (empty state).
     *
     * @return array<string, mixed>
     */
    function ui_page_labels(string $lang): array
    {
        $all = [
            'en' => [
                'brand' => 'Kimigo Travel', 'home' => 'Home', 'backHome' => 'Back to home',
                'prev' => 'Previous', 'next' => 'Next', 'pagination' => 'Pagination', 'pageOf' => 'Page {n} of {m}', 'breadcrumb' => 'Breadcrumb',
                'book' => 'Book via WhatsApp', 'readyToBook' => 'Ready to book', 'priceFrom' => 'Starting from', 'imageAlt' => 'Vehicle image', 'zoomImage' => 'View larger image',
                'readMore' => 'Read more', 'backNews' => 'Back to News & Information', 'otherNews' => 'More news & information', 'published' => 'Published',
                'pages' => [
                    'fleet'       => ['title' => 'Our Fleet', 'sub' => 'Pick the ride that fits your trip',
                        'desc' => 'Browse our complete fleet of comfortable, well-maintained vehicles for airport transfers, city tours and group trips. Book via WhatsApp.',
                        'empty' => 'No vehicles are available at the moment.'],
                    'gallery'     => ['title' => 'Gallery', 'sub' => 'Moments from our trips and fleet',
                        'desc' => 'See photos of our vehicles and the journeys we have shared with our guests.',
                        'empty' => 'The gallery is currently empty.'],
                    'news'        => ['title' => 'News & Information', 'sub' => 'Latest updates and helpful information',
                        'desc' => 'Read the latest news and announcements from our travel and car rental team, plus helpful information about our services, terms and travel tips.',
                        'empty' => 'No news or information to show yet.'],
                ],
            ],
            'id' => [
                'brand' => 'Kimigo Travel', 'home' => 'Beranda', 'backHome' => 'Kembali ke beranda',
                'prev' => 'Sebelumnya', 'next' => 'Berikutnya', 'pagination' => 'Navigasi halaman', 'pageOf' => 'Halaman {n} dari {m}', 'breadcrumb' => 'Jejak halaman',
                'book' => 'Pesan via WhatsApp', 'readyToBook' => 'Siap dipesan', 'priceFrom' => 'Harga dimulai dari', 'imageAlt' => 'Gambar kendaraan', 'zoomImage' => 'Perbesar gambar',
                'readMore' => 'Baca selengkapnya', 'backNews' => 'Kembali ke Berita & Informasi', 'otherNews' => 'Berita & informasi lainnya', 'published' => 'Diterbitkan',
                'pages' => [
                    'fleet'       => ['title' => 'Armada Kami', 'sub' => 'Pilih kendaraan yang pas untuk perjalananmu',
                        'desc' => 'Lihat seluruh armada kami yang nyaman dan terawat untuk antar-jemput bandara, city tour, dan perjalanan rombongan. Pesan via WhatsApp.',
                        'empty' => 'Belum ada kendaraan yang tersedia saat ini.'],
                    'gallery'     => ['title' => 'Galeri', 'sub' => 'Momen dari perjalanan dan armada kami',
                        'desc' => 'Lihat foto kendaraan kami dan perjalanan yang telah kami lalui bersama tamu.',
                        'empty' => 'Galeri masih kosong.'],
                    'news'        => ['title' => 'Berita & Informasi', 'sub' => 'Kabar terbaru dan informasi berguna',
                        'desc' => 'Baca berita dan pengumuman dari tim perjalanan dan rental mobil kami, serta informasi berguna tentang layanan, ketentuan, dan tips perjalanan.',
                        'empty' => 'Belum ada berita atau informasi.'],
                ],
            ],
            'ms' => [
                'brand' => 'Kimigo Travel', 'home' => 'Laman Utama', 'backHome' => 'Kembali ke laman utama',
                'prev' => 'Sebelumnya', 'next' => 'Seterusnya', 'pagination' => 'Navigasi halaman', 'pageOf' => 'Halaman {n} daripada {m}', 'breadcrumb' => 'Jejak laluan',
                'book' => 'Tempah melalui WhatsApp', 'readyToBook' => 'Sedia ditempah', 'priceFrom' => 'Harga bermula dari', 'imageAlt' => 'Gambar kenderaan', 'zoomImage' => 'Besarkan imej',
                'readMore' => 'Baca lagi', 'backNews' => 'Kembali ke Berita & Maklumat', 'otherNews' => 'Berita & maklumat lain', 'published' => 'Diterbitkan',
                'pages' => [
                    'fleet'       => ['title' => 'Armada Kami', 'sub' => 'Pilih kenderaan yang sesuai untuk perjalanan anda',
                        'desc' => 'Lihat keseluruhan armada kami yang selesa dan terjaga untuk pengangkutan lapangan terbang, lawatan bandar dan perjalanan berkumpulan.',
                        'empty' => 'Tiada kenderaan tersedia buat masa ini.'],
                    'gallery'     => ['title' => 'Galeri', 'sub' => 'Detik daripada perjalanan dan armada kami',
                        'desc' => 'Lihat foto kenderaan kami dan perjalanan yang telah kami kongsikan bersama tetamu.',
                        'empty' => 'Galeri masih kosong.'],
                    'news'        => ['title' => 'Berita & Maklumat', 'sub' => 'Perkembangan terkini dan maklumat berguna',
                        'desc' => 'Baca berita dan pengumuman daripada pasukan pelancongan dan sewa kereta kami, serta maklumat berguna tentang perkhidmatan, syarat dan petua perjalanan.',
                        'empty' => 'Belum ada berita atau maklumat.'],
                ],
            ],
        ];

        return $all[$lang] ?? $all['en'];
    }
}

if (! function_exists('ui_date')) {
    /**
     * Date as "d F Y" with the month name in the given language, e.g. 15 November 2026,
     * 15 Agustus 2026 (id), 15 Ogos 2026 (ms). Empty or unparsable input gives ''.
     */
    function ui_date(string $value, string $lang = 'en'): string
    {
        $ts = trim($value) !== '' ? strtotime($value) : false;
        if ($ts === false) {
            return '';
        }

        $months = [
            'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'id' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            'ms' => ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'],
        ];
        $names = $months[$lang] ?? $months['en'];

        return date('d', $ts) . ' ' . $names[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }
}