# Rencana Implementasi: Redesain UI Berbasis Referensi

## Ringkasan

Implementasi dikerjakan dari bawah ke atas dalam bahasa PHP 8.x (CodeIgniter 4.7), CSS, dan JavaScript vanilla, hanya pada lapisan presentasi: (1) token desain dan font lokal, (2) helper view murni `ui_helper.php`, (3) layout dan view Landing beserta CSS/JS, (4) shell Admin, (5) dashboard Admin beserta `dashboard.js`, lalu (6) verifikasi akhir. Tidak ada perubahan pada controller, model, route, filter, migrasi, atau `api/v1`.

Tes properti memakai **Eris** (`giorgiosironi/eris`) dengan **minimal 100 iterasi**, diberi tag `// Feature: ui-redesign-reference, Property {n}: {teks properti}`. Tes yang sudah ada tidak boleh rusak (lihat bagian "Struktur Hook yang Dipertahankan" di design.md). Teks UI landing ditulis dalam tiga bahasa (en/id/ms); UI admin tetap berbahasa Inggris.

## Tasks

- [x] 1. Siapkan token desain dan font lokal
  - [x] 1.1 Bundel font lokal dan catat sumbernya
    - Unduh sekali (saat pengembangan) Plus Jakarta Sans dan Figtree variable woff2 subset latin ke `public/assets/vendor/fonts/plus-jakarta-sans/PlusJakartaSans-latin-wght.woff2` dan `public/assets/vendor/fonts/figtree/Figtree-latin-wght.woff2`, beserta berkas lisensi OFL di masing-masing folder
    - Perbarui `public/assets/vendor/README.md` dengan versi, lisensi, dan URL sumber setiap font; pastikan tidak ada referensi CDN yang dipakai saat runtime
    - _Requirements: 1.3, 1.5, 1.6_

  - [x] 1.2 Buat `public/assets/css/tokens.css`
    - Tulis `@font-face` (dengan `font-display: swap`) dan semua custom property sesuai bagian "Token Desain" di design.md (netral, landing, admin, fokus, radius, bayangan, tipografi, spasi)
    - Sertakan tumpukan font sistem cadangan pada `--font-landing` dan `--font-admin`
    - _Requirements: 1.1, 1.3, 1.4, 1.7_

  - [x]* 1.3 Tulis tes smoke aset dan tes properti kontras token
    - Buat `tests/unit/TokensAssetsSmokeTest.php`: `tokens.css`, `landing.css`, `admin.css`, dan kedua berkas woff2 ada dan tidak kosong; `tokens.css` tidak memuat `http://`/`https://` kecuali komentar; tidak ada `cdn.`, `jsdelivr`, `fonts.googleapis`
    - Buat `tests/unit/TokenContrastPropertyTest.php` yang membaca nilai heksadesimal dari `tokens.css`, menghitung rasio WCAG, dan memeriksa seluruh pasangan matriks kontras
    - **Property 6: Pasangan warna token memenuhi kontras**
    - **Validates: Requirements 1.1, 1.3, 11.1, 11.2, 14.3, 14.4**

- [x] 2. Implementasi UI_Helper (fungsi murni)
  - [x] 2.1 Buat `app/Helpers/ui_helper.php`
    - Implementasikan `ui_landing_stats`, `ui_highlight_last_word`, `ui_initials`, `ui_nav_is_active`, dan `ui_admin_date` sesuai pseudokode di design.md
    - Pastikan fungsi dibungkus `if (! function_exists(...))`, tanpa akses DB, sesi, atau jaringan; `ui_highlight_last_word` memakai `esc()` sebelum membungkus `<mark class="hl">`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 12.4, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7, 13.8, 13.9_

  - [x]* 2.2 Tulis tes properti untuk `ui_landing_stats`
    - Buat `tests/unit/UiStatsPropertyTest.php`
    - **Property 1: Statistik landing selalu valid dan terurut**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6**

  - [x]* 2.3 Tulis tes properti untuk `ui_highlight_last_word`
    - Buat `tests/unit/UiHighlightPropertyTest.php` dengan generator string unicode, spasi saja, dan karakter markup
    - **Property 2: Sorotan kata terakhir aman dan menjaga teks**
    - **Validates: Requirements 3.2, 13.1, 13.2, 13.3, 13.4**

  - [x]* 2.4 Tulis tes properti untuk `ui_initials`
    - Buat `tests/unit/UiInitialsPropertyTest.php`
    - **Property 3: Inisial avatar selalu pendek dan aman**
    - **Validates: Requirements 7.4, 7.8, 13.5, 13.6**

  - [x]* 2.5 Tulis tes properti untuk `ui_nav_is_active`
    - Buat `tests/unit/UiNavActivePropertyTest.php` dengan daftar item nav admin dan path acak (termasuk `admin/newsletter`)
    - **Property 4: Item navigasi aktif konsisten**
    - **Validates: Requirements 7.7, 13.7, 13.8**

  - [x]* 2.6 Tulis tes properti untuk `ui_admin_date`
    - Buat `tests/unit/UiAdminDatePropertyTest.php`
    - **Property 5: Format tanggal header admin dapat di-parse**
    - **Validates: Requirements 7.9, 13.9**

- [x] 3. Redesain Landing (layout, view, CSS, JS)
  - [x] 3.1 Ubah `app/Views/layouts/public.php`
    - Tambah `tokens.css` sebelum `landing.css` dan preload font Plus Jakarta Sans; ganti navbar menjadi pil putih dengan logo mark lime tetapi pertahankan `navbar navbar-expand-lg`, `.navbar-toggler` + `#site-nav`, `ul.lang-selector` (3 tautan), skip-link, `main#main`, `a.wa-float`, dan aset lokal
    - Tambah label baru tiga bahasa pada `$layoutLabels`; footer menjadi kartu gelap membulat
    - _Requirements: 1.2, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 6.1, 6.2, 12.2, 12.5_

  - [x] 3.2 Ubah `app/Views/landing/index.php`
    - Panggil `helper('ui')`; tambah label baru (`viewFleet`, `readyToBook`, `trust`, `pause`, `play`, `statFleet`, `statGallery`, `statNews`, `statInformation`, `statFaq`, `moreFleetSubtitle`) pada en/id/ms tanpa mengubah label lama
    - Susun hero: `h1#hero-title` dengan `ui_highlight_last_word`, subjudul, CTA `.btn-wa`, pil kepercayaan (hanya jika armada > 0), Hero_Media_Card dengan carousel `#hero-carousel` (kontrol/indikator menargetkan `#hero-carousel`, hanya jika slide > 1) atau `<div class="hero">` placeholder, Lime_Pill "Lihat Armada" dan `[data-hero-toggle]` (hanya jika slide > 1); h1 tetap tunggal
    - Render Stats_Row dari `ui_landing_stats`; restyle bagian about, fleet (Fleet_Rail + chip + atribut `data-*` booking), gallery, news (kartu fitur gelap + kartu putih), information, faq (kelas accordion persis), contact; pertahankan id bagian, pesan kosong, dan escape `esc()`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 4.6, 4.7, 4.8, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 6.1, 6.2, 6.3, 6.4_

  - [x] 3.3 Tulis ulang `public/assets/css/landing.css` di atas token
    - Gaya mobile-first untuk body gradasi, navbar pil, hero, Hero_Media_Card (browser frame), Lime_Pill, Stats_Row, section card, Fleet_Rail (scroll-snap <992px, grid 3 kolom >=992px), galeri, berita, informasi, FAQ, kontak, footer, `wa-float`
    - Fokus `outline: 3px solid var(--focus-ring)` offset 2px (`--focus-ring-on-dark` pada permukaan gelap), target sentuh >= 44px, `prefers-reduced-motion` mematikan smooth scroll dan transisi carousel; lime dan hijau `#2e9e6b` hanya sebagai latar; tanpa warna heksadesimal literal di luar token
    - _Requirements: 1.4, 2.5, 4.7, 5.3, 5.4, 5.5, 10.1, 10.2, 10.3, 11.3, 11.4, 11.5, 11.6_

  - [x] 3.4 Perbarui `public/assets/js/landing.js`
    - Tambahkan handler `[data-hero-toggle]` (jeda/putar carousel `#hero-carousel` via Bootstrap Carousel API, ubah `aria-pressed`, ikon, dan label); tangani rel armada agar fokus keyboard memunculkan kartu terfokus; hapus kode `.nav-toggle` lama yang tak terpakai tanpa mengganggu handler FAQ/tracking yang ada
    - JS harus toleran bila elemen tidak ada
    - _Requirements: 3.9, 11.8_

  - [x]* 3.5 Tulis tes properti struktur landing
    - Buat `tests/unit/LandingStructurePropertyTest.php` yang merender `landing/index` dengan data acak (0-8 slide, daftar acak, bahasa en/id/ms)
    - **Property 7: Struktur landing stabil untuk data apa pun**
    - **Validates: Requirements 3.1, 3.4, 3.5, 3.6, 3.7, 3.9, 4.6, 5.1, 11.7**

  - [x]* 3.6 Tulis tes properti gating WhatsApp
    - Buat `tests/unit/LandingWhatsappGatingPropertyTest.php`
    - **Property 8: Elemen WhatsApp hanya muncul bila tautan ada**
    - **Validates: Requirements 2.6, 2.7, 3.10, 5.6, 5.7**

  - [x]* 3.7 Tulis tes properti escaping landing
    - Buat `tests/unit/LandingEscapingPropertyTest.php` dengan generator string berisi `<script>`, `"`, `'`, `&`
    - **Property 9: Semua teks data landing ter-escape**
    - **Validates: Requirements 5.15, 3.10**

  - [x]* 3.8 Tulis tes properti kelengkapan label
    - Buat `tests/unit/LandingLabelsPropertyTest.php` yang memeriksa kunci label identik dan tidak kosong untuk en/id/ms pada landing dan layout publik, serta fallback bahasa tak dikenal ke `en`
    - **Property 10: Label tiga bahasa lengkap dan fallback ke en**
    - **Validates: Requirements 6.1, 6.2, 6.3**

  - [x] 3.9 Perbarui tes landing yang ada
    - `tests/unit/LandingViewTest.php`: pertahankan semua asersi lama; tambah asersi target `#hero-carousel`, tepat satu `<h1`, `<mark class="hl">` di dalam h1, Stats_Row absen saat semua data kosong, chip "Ready to book" hanya saat WhatsApp ada
    - `tests/unit/VendorAssetsLayoutsSmokeTest.php` (bagian publik): layout publik memuat `tokens.css` sebelum `landing.css`, tanpa `https://cdn.`, `jsdelivr`, `fonts.googleapis`
    - _Requirements: 12.3, 14.2_

- [x] 4. Checkpoint - Landing
  - Pastikan semua tes pada tahap landing lulus (`vendor/bin/phpunit --testsuite unit`), tanyakan kepada pengguna bila ada pertanyaan.

- [x] 5. Redesain shell Admin
  - [x] 5.1 Buat `app/Views/admin/partials/page_header.php`
    - Partial menerima `$title` dan `$subtitle` opsional, merender `h1` besar tebal dan subjudul tanggal; sediakan slot aksi untuk tombol (mis. "Create new")
    - _Requirements: 7.9, 11.7_

  - [x] 5.2 Ubah `app/Views/admin/layout.php`
    - Panggil `helper('ui')`; muat `tokens.css` sebelum `admin.css`; ganti `.admin-shell` menjadi `.admin-frame` di atas latar biru; pertahankan `aside#adminSidebar.offcanvas-lg offcanvas-start`, `#adminSidebarLabel`, tombol tutup berlabel, meta `csrf-*`/`base-url`/`robots noindex`, `#toast-region`, dan skrip lokal
    - Sidebar: brand, kartu profil (dropdown dengan inisial `ui_initials`, nama dari `session('admin_username')` dengan fallback "Admin", peran "Admin", chevron), nav primer (Dashboard, Information, News, Fleet, FAQ, Gallery), pembatas, nav sekunder (Site config, View website `rel="noopener"` tab baru, Sign out); item aktif memakai `ui_nav_is_active` + `aria-current="page"`
    - Topbar mobile (hamburger + brand); state belum login merender kartu putih membulat di atas latar biru tanpa sidebar; jangan menambah kontrol tanpa fungsi
    - _Requirements: 1.2, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.10, 7.11, 7.12, 12.2_

  - [x] 5.3 Restyle `signin.php` dan `admin/partials/list_table.php`
    - `list_table.php`: pakai `page_header` untuk `h1` + subjudul tanggal dan tombol "Create new"; pertahankan `.table-responsive`, `data-admin-list`, `data-entity`, `data-page`, `th[data-key]`, `#list-status`, `[data-admin-pager]`, `[data-page-prev|next|info]`
    - `signin.php`: kartu putih membulat dengan input berlabel (`for`/`id`/`name` tetap)
    - Biarkan markup form partials (`lang_field`, `form_open`, dst.) dan hook `admin.js` tidak berubah
    - _Requirements: 7.9, 7.11, 9.1, 9.2, 9.3, 9.4, 10.6_

  - [x] 5.4 Tulis ulang `public/assets/css/admin.css` di atas token
    - Latar biru + `.admin-frame` (radius 24px >=992px, 0 di bawahnya), sidebar 15rem sticky, `.profile-card`, nav primer/sekunder dengan state aktif biru, page header, `.stat-card` + tile panah, `.chart-card`, `.donut-card`/`.donut-legend`, `.list-card`, tabel/form/tab bahasa/toast/validasi (kontras >= 4.5:1), halaman signin
    - Fokus `--focus-ring`, target sentuh >= 44px, `prefers-reduced-motion`; pertahankan kelas legacy yang dipakai `admin.js` (`.invalid`, `.field-error`, `.lang-tabs`, `.pager`, `.admin-table`); tanpa warna heksadesimal literal di luar token
    - _Requirements: 7.1, 7.2, 8.2, 9.1, 9.5, 10.4, 10.5, 11.3, 11.4, 11.5, 11.6_

- [x] 6. Redesain dashboard Admin
  - [x] 6.1 Ubah `app/Views/admin/dashboard.php`
    - Gunakan `page_header` (judul "Dashboard", subjudul tanggal + "Welcome, {username}"); pertahankan `#dashboard-empty`, `#whatsapp-warning`, `#dashboard-data`, semua teks kosong dan teks "Published N"/"Draft N"
    - Delapan Stat_Card dengan `fs-3 fw-semibold` + tile panah dekoratif `aria-hidden`; lima kartu konten dengan tile tautan `aria-label="Open {Entity}"` ke `admin/<entitas>`
    - Chart_Card: `canvas#chart-daily` (+ `select#daily-range` 7/14/30), `canvas#chart-weekly` + `#chart-weekly-empty`, dua donut-card dengan `#chart-language`/`#chart-device`, `#total-*`, `ul#legend-*`, dan `#chart-*-empty`; semua canvas `role="img"` + `aria-label`
    - List_Card: referrer teratas dan armada teratas sebagai `<ol>` berbilah proporsi, berita terbaru dengan tile ikon dan badge tanggal, login terakhir; tepat satu referensi `chart.umd.min.js` lokal
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.12, 8.13, 8.14, 8.15, 8.16, 8.17, 11.7, 11.9_

  - [x] 6.2 Perbarui `public/assets/js/dashboard.js` (aditif)
    - Pertahankan kontrak lama (`readData`, `showEmpty/hideEmpty`, fallback tanpa Chart.js); pindahkan warna ke satu objek `THEME`
    - Garis `#chart-daily`: hijau halus, area gradasi, titik penanda, tooltip gelap dengan panduan vertikal putus-putus; `sliceDaily` untuk `#daily-range`; batang berkelompok `#chart-weekly` (7 hari terakhir, page views vs unique); donat dengan `cutout 68%`, total tengah, dan legenda HTML (label, persen, jumlah)
    - Nonaktifkan animasi saat `prefers-reduced-motion: reduce`; semua fitur baru dilewati tanpa error bila elemen hook tidak ada
    - _Requirements: 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 8.11, 8.18_

  - [x]* 6.3 Tulis tes properti render dashboard
    - Buat `tests/unit/DashboardHooksPropertyTest.php` dengan generator ringkasan parsial/kosong/non-numerik, merender `admin/dashboard` dan memeriksa angka kartu, hook canvas/empty, `#dashboard-empty`, `#whatsapp-warning`, dan teks kosong daftar
    - **Property 11: Dashboard memetakan data ringkasan ke hook dan keadaan kosong**
    - **Validates: Requirements 8.1, 8.4, 8.5, 8.12, 8.13, 8.14, 8.15, 11.7**

  - [x]* 6.4 Tulis tes properti round trip JSON dashboard
    - Buat `tests/unit/DashboardJsonRoundTripPropertyTest.php` dengan string unicode, kutip, `<`, `&`
    - **Property 12: JSON dashboard bersifat round trip**
    - **Validates: Requirements 8.17**

  - [x] 6.5 Perbarui tes admin yang ada
    - `tests/unit/DashboardViewTest.php`: pertahankan asersi lama; tambah `canvas id="chart-weekly"`, `chart-weekly-empty`, `donut-legend`, `aria-label="Open Fleet"`
    - `tests/unit/AdminViewsTest.php`: tambah `aria-current="page"` pada nav aktif, inisial pada kartu profil, `offcanvas-lg` tetap, signin tanpa sidebar
    - `tests/unit/VendorAssetsLayoutsSmokeTest.php` (bagian admin): layout admin memuat `tokens.css` sebelum `admin.css`, tanpa CDN/layanan font eksternal
    - _Requirements: 12.3, 14.2_

- [x] 7. Verifikasi akhir
  - [x] 7.1 Jalankan seluruh suite dan periksa batas perubahan
    - Jalankan `vendor/bin/phpunit` dan pastikan `ArchitectureConformanceTest` lulus tanpa modifikasi
    - Periksa `git diff --stat` bahwa tidak ada perubahan di `app/Controllers`, `app/Models`, `app/Config/Routes.php`, `app/Filters`, `app/Database`; jika ada, kembalikan
    - Perbaiki kegagalan tes yang disebabkan redesain tanpa melemahkan asersi hook
    - _Requirements: 12.1, 12.2, 12.3, 12.5, 14.5_

- [x] 8. Checkpoint akhir - Pastikan semua tes lulus
  - Pastikan semua tes lulus, tanyakan kepada pengguna bila ada pertanyaan.

## Catatan

- Tugas bertanda `*` bersifat opsional (tes) dan dapat dilewati untuk MVP yang lebih cepat.
- Setiap tugas merujuk persyaratan spesifik untuk ketertelusuran; properti 1-12 masing-masing diimplementasikan oleh tepat satu tes properti.
- Pemeriksaan visual responsif (360/768/1024/1440 px) dan keterbacaan dilakukan manual; tampilan visual tidak cocok untuk pengujian properti.
- Hanya presentasi yang berubah; arsitektur tiga tingkat dan kontrak data tidak disentuh.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "2.1"] },
    { "id": 1, "tasks": ["1.3", "2.2", "2.3", "2.4", "2.5", "2.6", "3.1", "3.2", "5.1", "5.2"] },
    { "id": 2, "tasks": ["3.3", "3.4", "3.5", "3.6", "3.7", "3.8", "5.3", "6.1"] },
    { "id": 3, "tasks": ["3.9", "5.4", "6.2", "6.3", "6.4"] },
    { "id": 4, "tasks": ["6.5"] },
    { "id": 5, "tasks": ["7.1"] }
  ]
}
```
