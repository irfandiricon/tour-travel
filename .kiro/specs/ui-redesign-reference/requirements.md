# Dokumen Persyaratan: Redesain UI Berbasis Referensi

## Pendahuluan

Fitur ini mendesain ulang lapisan presentasi situs Wisata & Rental Mobil (CodeIgniter 4.7): halaman Landing publik mengikuti Referensi B ("Videmy") dan UI Admin mengikuti Referensi A ("Acadex"). Perubahan terbatas pada view, CSS, JS presentasi, satu helper view, aset font lokal, dan tes. Route, controller, model, API `api/v1/*`, skema database, id, atribut `data-*`, tiga bahasa (en/id/ms), dan aset lokal tanpa CDN tetap berlaku seperti pada spec `travel-rental-website`.

## Glosarium

- **Landing_Page**: Halaman publik satu-halaman yang dirender oleh `layouts/public.php` dan `landing/index.php`.
- **Admin_UI**: Seluruh halaman admin yang dirender melalui `admin/layout.php` (dashboard, daftar, formulir, site config, signin).
- **Design_Tokens**: Variabel CSS kustom (warna, radius, bayangan, tipografi, spasi) yang didefinisikan di `public/assets/css/tokens.css`.
- **UI_Helper**: Berkas `app/Helpers/ui_helper.php` berisi fungsi murni untuk view (`ui_landing_stats`, `ui_highlight_last_word`, `ui_initials`, `ui_nav_is_active`, `ui_admin_date`).
- **Stats_Row**: Baris 1-3 kartu statistik di Landing_Page yang dihitung dari data nyata.
- **Hero_Media_Card**: Kartu media bergaya jendela browser di hero Landing_Page berisi carousel atau placeholder.
- **Lime_Pill**: Tombol pil berlatar lime (`--lp-lime`) dengan teks `--lp-lime-ink`.
- **Fleet_Rail**: Daftar horizontal kartu armada potret ber-scroll-snap pada layar kecil.
- **App_Frame**: Bingkai aplikasi putih membulat di Admin_UI yang membungkus sidebar dan konten, di atas latar biru.
- **Sidebar**: Panel navigasi Admin_UI (offcanvas di bawah breakpoint `lg`).
- **Stat_Card**: Kartu angka Admin_UI dengan label, angka besar, dan tile ikon panah.
- **Chart_Card**: Kartu Admin_UI berisi grafik Chart.js (garis, batang, atau donat).
- **List_Card**: Kartu Admin_UI berisi daftar (berita terbaru, referrer teratas, armada teratas).
- **Dashboard_Hooks**: Himpunan id/atribut dashboard: `#dashboard-data`, `#chart-daily`, `#chart-language`, `#chart-device`, `#chart-daily-empty`, `#chart-language-empty`, `#chart-device-empty`, `#dashboard-empty`, `#whatsapp-warning`.
- **Preserved_Hooks**: Hook yang wajib tetap seperti dirinci pada bagian "Struktur Hook yang Dipertahankan" di design.md.
- **Local_Assets**: Aset yang dilayani dari `public/assets/` tanpa permintaan jaringan ke domain pihak ketiga.
- **Reduced_Motion**: Preferensi sistem `prefers-reduced-motion: reduce`.

## Persyaratan

### Persyaratan 1: Design tokens dan font lokal

**User Story:** Sebagai pengembang, saya ingin satu sumber token desain dan font yang dibundel lokal, agar tampilan konsisten dan tidak bergantung pada CDN.

#### Acceptance Criteria

1. THE Design_Tokens SHALL mendefinisikan custom property untuk warna netral, warna landing, warna admin, warna fokus, radius (8, 12, 16, 24, 999 px), bayangan (sm, md, lg), tipografi, dan spasi bagian.
2. THE Landing_Page SHALL memuat `tokens.css` sebelum `landing.css`, dan THE Admin_UI SHALL memuat `tokens.css` sebelum `admin.css`.
3. THE Landing_Page SHALL menggunakan font Plus Jakarta Sans yang dilayani dari `public/assets/vendor/fonts/`, dan THE Admin_UI SHALL menggunakan font Figtree yang dilayani dari `public/assets/vendor/fonts/`.
4. THE Landing_Page dan THE Admin_UI SHALL menyertakan tumpukan font sistem sebagai cadangan pada `font-family`.
5. THE Landing_Page dan THE Admin_UI SHALL hanya merujuk Local_Assets, tanpa URL CDN atau layanan font pihak ketiga.
6. THE `public/assets/vendor/README.md` SHALL mencatat versi, lisensi, dan sumber unduhan setiap berkas font.
7. WHEN berkas font gagal dimuat, THE Landing_Page dan THE Admin_UI SHALL menampilkan teks dengan font sistem cadangan tanpa kehilangan konten.

### Persyaratan 2: Navbar dan layout Landing

**User Story:** Sebagai pengunjung, saya ingin navigasi minimalis yang rapi di desktop dan mobile, agar mudah berpindah antarbagian dan bahasa.

#### Acceptance Criteria

1. THE Landing_Page SHALL menampilkan navbar sticky berbentuk pil putih dengan logo mark persegi lime, nama brand, tautan bagian, dan dropdown bahasa.
2. WHILE lebar viewport kurang dari 992 px, THE Landing_Page SHALL menyembunyikan tautan bagian di balik tombol hamburger `.navbar-toggler` yang mengendalikan `#site-nav`.
3. THE Landing_Page SHALL mempertahankan `ul.lang-selector` dengan tepat tiga tautan menuju `lang/en`, `lang/id`, dan `lang/ms`.
4. THE Landing_Page SHALL mempertahankan `navbar-expand-lg`, tautan lewati-ke-konten menuju `#main`, dan elemen `main#main`.
5. THE Landing_Page SHALL menampilkan latar halaman berupa gradasi vertikal dari biru muda ke putih.
6. WHERE alamat WhatsApp tersedia, THE Landing_Page SHALL menampilkan tombol mengambang `a.wa-float` dengan atribut `class="wa-float"` persis.
7. WHERE alamat WhatsApp tidak tersedia, THE Landing_Page SHALL tidak merender elemen `wa-float`.

### Persyaratan 3: Hero dan kartu media

**User Story:** Sebagai pengunjung, saya ingin hero dengan judul besar dan kartu media yang menarik, agar langsung memahami layanan dan dapat melihat armada atau memesan.

#### Acceptance Criteria

1. THE Landing_Page SHALL merender tepat satu elemen `<h1 id="hero-title">` berisi judul hero, atau teks "Welcome"/"Selamat Datang" sesuai bahasa bila judul kosong.
2. THE Landing_Page SHALL membungkus kata terakhir judul hero dengan `<mark class="hl">` setelah meng-escape seluruh teks judul.
3. THE Landing_Page SHALL menampilkan Hero_Media_Card dengan tiga titik dekoratif berstatus `aria-hidden="true"`.
4. WHEN `heroSlides` berisi minimal satu slide, THE Landing_Page SHALL merender carousel `#hero-carousel` dengan satu `class="carousel-item"` per slide (maksimal lima), `data-bs-ride="carousel"`, dan `data-bs-interval="5000"`.
5. WHEN `heroSlides` kosong, THE Landing_Page SHALL merender elemen placeholder `class="hero"` di dalam Hero_Media_Card dan tidak merender `data-bs-ride="carousel"`.
6. WHEN `heroSlides` berisi lebih dari satu slide, THE Landing_Page SHALL merender indikator, kontrol sebelumnya/berikutnya, dan `data-bs-slide-to` satu per slide dengan target `#hero-carousel`.
7. WHEN `heroSlides` berisi tepat satu slide atau kosong, THE Landing_Page SHALL tidak merender `carousel-indicators`, `carousel-control-prev`, dan `carousel-control-next`.
8. THE Landing_Page SHALL menampilkan Lime_Pill "Lihat Armada" (terjemahan sesuai bahasa) yang menaut ke `#fleet`.
9. WHEN `heroSlides` berisi lebih dari satu slide, THE Landing_Page SHALL menampilkan Lime_Pill `[data-hero-toggle]` yang menjeda dan memutar carousel dan mengatur `aria-pressed` sesuai status.
10. WHERE alamat WhatsApp tersedia, THE Landing_Page SHALL menampilkan tombol `.btn-wa` pada hero intro dengan tautan WhatsApp ter-escape sebagai atribut.
11. WHERE jumlah armada lebih dari nol, THE Landing_Page SHALL menampilkan pil kepercayaan berisi jumlah armada dari data.
12. WHEN jumlah armada sama dengan nol, THE Landing_Page SHALL tidak menampilkan pil kepercayaan.

### Persyaratan 4: Baris statistik dari data nyata

**User Story:** Sebagai pengunjung, saya ingin melihat angka ringkas tentang layanan, agar percaya pada kelengkapan situs, tanpa angka rekayasa.

#### Acceptance Criteria

1. THE UI_Helper SHALL menyediakan `ui_landing_stats(array $counts, array $labels)` yang mengembalikan daftar kartu dengan kunci `key`, `value`, `label`, dan `dark`.
2. THE `ui_landing_stats` SHALL mengurutkan kandidat menurut prioritas fleet, gallery, news, information, faq.
3. THE `ui_landing_stats` SHALL hanya menyertakan kandidat berhitungan lebih dari nol dan berlabel tersedia.
4. THE `ui_landing_stats` SHALL mengembalikan paling banyak tiga kartu.
5. THE `ui_landing_stats` SHALL menandai `dark = true` hanya pada kartu pertama.
6. WHEN seluruh hitungan sama dengan nol, THE `ui_landing_stats` SHALL mengembalikan daftar kosong, dan THE Landing_Page SHALL tidak merender Stats_Row.
7. THE Landing_Page SHALL merender kartu pertama Stats_Row dengan latar `--c-ink` dan angka putih, dan kartu lainnya dengan latar putih.
8. THE Landing_Page SHALL menampilkan nilai hitungan apa adanya tanpa akhiran "+" atau pembulatan.

### Persyaratan 5: Bagian konten Landing

**User Story:** Sebagai pengunjung, saya ingin setiap bagian konten tampil dalam kartu membulat yang konsisten, agar mudah dibaca di semua perangkat.

#### Acceptance Criteria

1. THE Landing_Page SHALL mempertahankan id bagian `top`, `about`, `fleet`, `gallery`, `news`, `information`, `faq`, dan `contact`.
2. THE Landing_Page SHALL menampilkan setiap judul bagian sebagai `h2` terpusat dengan subjudul muted bila tersedia.
3. THE Landing_Page SHALL merender setiap armada sebagai kartu potret dengan rasio gambar 4:5 di dalam Fleet_Rail.
4. WHILE lebar viewport kurang dari 992 px, THE Fleet_Rail SHALL berupa rel horizontal dengan `scroll-snap-type: x mandatory`, `tabindex="0"`, dan `aria-label` terlokalisasi.
5. WHILE lebar viewport minimal 992 px, THE Fleet_Rail SHALL tampil sebagai grid tiga kolom.
6. WHERE tautan WhatsApp armada tersedia, THE Landing_Page SHALL menampilkan chip lime "Siap dipesan"/"Ready to book"/"Sedia ditempah" dan tombol `.btn-wa` dengan teks "Book via WhatsApp" (terjemahan sesuai bahasa) serta atribut `data-track-booking`, `data-fleet-id`, dan `data-fleet-name`.
7. WHEN tautan WhatsApp armada bernilai null, THE Landing_Page SHALL tidak merender chip "Siap dipesan" maupun tombol pemesanan untuk armada tersebut.
8. THE Landing_Page SHALL merender galeri sebagai grid membulat dengan `figure` dan `figcaption` untuk setiap item yang memiliki keterangan.
9. THE Landing_Page SHALL merender berita terbaru (item pertama) sebagai kartu fitur gelap dan berita lainnya sebagai kartu putih, dengan tanggal dalam `.card-date`.
10. THE Landing_Page SHALL merender setiap item informasi sebagai kartu off-white dengan ikon centang dan teks judul serta isi.
11. THE Landing_Page SHALL merender FAQ sebagai accordion dengan `class="accordion"`, `class="accordion-item"`, dan tombol `data-bs-toggle="collapse" data-bs-target="#faq-a-N"`.
12. THE Landing_Page SHALL merender bagian kontak sebagai kartu charcoal berisi isi kontak, email, alamat, dan tombol WhatsApp lime bila tersedia.
13. IF suatu bagian tidak memiliki data, THEN THE Landing_Page SHALL menampilkan pesan kosong terlokalisasi yang sama dengan versi sebelum redesain.
14. THE Landing_Page SHALL tidak membuat bagian testimoni, harga, atau chip filter kategori karena tidak ada data pendukungnya.
15. THE Landing_Page SHALL melarikan (escape) seluruh teks dari data sebelum dikeluarkan ke HTML.

### Persyaratan 6: Tiga bahasa pada Landing

**User Story:** Sebagai pengunjung berbahasa Indonesia, Melayu, atau Inggris, saya ingin semua teks baru tersedia dalam bahasa saya.

#### Acceptance Criteria

1. THE Landing_Page SHALL menyediakan label baru (`viewFleet`, `readyToBook`, `trust`, `pause`, `play`, `statFleet`, `statGallery`, `statNews`, `statInformation`, `statFaq`) untuk bahasa `en`, `id`, dan `ms`.
2. FOR ALL bahasa yang didukung, THE Landing_Page SHALL memiliki kumpulan kunci label yang identik.
3. WHEN bahasa tidak dikenal diberikan, THE Landing_Page SHALL memakai label bahasa `en`.
4. THE Landing_Page SHALL mempertahankan seluruh teks label lama yang dipakai tes (pesan kosong dan "Book via WhatsApp").

### Persyaratan 7: Shell Admin (bingkai, sidebar, header)

**User Story:** Sebagai admin, saya ingin shell aplikasi berbingkai dengan sidebar dan header yang jelas, agar navigasi cepat dan mudah.

#### Acceptance Criteria

1. WHILE admin sudah masuk, THE Admin_UI SHALL merender App_Frame putih membulat (radius 24 px) di atas latar `--ad-backdrop`.
2. THE Admin_UI SHALL merender Sidebar sebagai `aside#adminSidebar` dengan kelas `offcanvas-lg offcanvas-start`, `aria-labelledby="adminSidebarLabel"`, dan lebar 15 rem pada layar minimal 992 px.
3. WHILE lebar viewport kurang dari 992 px, THE Sidebar SHALL berfungsi sebagai offcanvas yang dibuka oleh tombol hamburger di topbar dan memiliki tombol tutup berlabel.
4. THE Sidebar SHALL menampilkan brand, kartu profil (avatar berinisial dari `ui_initials`, nama dari sesi `admin_username`, peran "Admin", chevron), navigasi primer, pembatas, dan navigasi sekunder.
5. THE Sidebar SHALL memuat navigasi primer Dashboard, Information, News, Fleet, FAQ, Gallery dengan tautan ke route yang sudah ada.
6. THE Sidebar SHALL memuat navigasi sekunder Site config, View website (route `/`, tab baru, `rel="noopener"`), dan Sign out (route `signout`).
7. WHEN path permintaan cocok dengan suatu item navigasi menurut `ui_nav_is_active`, THE Sidebar SHALL memberi item itu kelas `active` dan `aria-current="page"`.
8. IF sesi tidak memuat `admin_username`, THEN THE kartu profil SHALL menampilkan nama "Admin" dan inisial "A".
9. THE Admin_UI SHALL menampilkan header halaman berisi `h1` besar bertebal dan subjudul tanggal dari `ui_admin_date` pada dashboard dan halaman daftar.
10. THE Admin_UI SHALL tidak menampilkan kontrol tanpa fungsi (misalnya tombol pencarian atau pesan tanpa tujuan).
11. WHILE admin belum masuk, THE Admin_UI SHALL merender konten (signin) di dalam kartu putih membulat di atas latar `--ad-backdrop` tanpa Sidebar.
12. THE Admin_UI SHALL mempertahankan meta `csrf-*`, `base-url`, `robots noindex, nofollow`, dan `#toast-region`.

### Persyaratan 8: Dashboard Admin (kartu, chart, daftar)

**User Story:** Sebagai admin, saya ingin dashboard dengan kartu statistik, grafik, dan daftar yang mudah dibaca, agar memantau pengunjung dan pemesanan dengan cepat.

#### Acceptance Criteria

1. THE Admin_UI SHALL merender delapan Stat_Card pengunjung/pemesanan dengan angka pada elemen berkelas `fs-3 fw-semibold`, sama dengan data ringkasan atau nol bila data hilang.
2. THE Admin_UI SHALL menambahkan tile ikon panah pada setiap Stat_Card, dengan tile dekoratif `aria-hidden="true"` untuk delapan kartu pengunjung/pemesanan.
3. THE Admin_UI SHALL merender lima kartu konten (Fleet, News, Information, FAQ, Gallery) dengan teks "Published N" dan "Draft N" pada badge `badge text-bg-success` dan `badge text-bg-secondary`, serta tile panah berupa tautan `admin/<entitas>` berlabel `aria-label="Open <Entity>"`.
4. THE Admin_UI SHALL mempertahankan Dashboard_Hooks dengan nama dan tipe elemen yang sama.
5. THE Admin_UI SHALL menambahkan `canvas#chart-weekly` dengan `#chart-weekly-empty` sebagai Chart_Card batang berkelompok.
6. THE `dashboard.js` SHALL menggambar `#chart-daily` sebagai garis hijau halus dengan area gradasi, titik penanda, dan tooltip gelap.
7. THE `dashboard.js` SHALL memotong data harian sesuai nilai `#daily-range` (7, 14, atau 30 hari) di sisi klien.
8. THE `dashboard.js` SHALL menggambar `#chart-language` dan `#chart-device` sebagai donat dengan total di tengah dan legenda HTML berisi label, persen, dan jumlah.
9. WHEN jumlah total suatu chart sama dengan nol, THE `dashboard.js` SHALL menyembunyikan canvas terkait dan menampilkan elemen `#<canvasId>-empty`.
10. IF `window.Chart` bukan fungsi, THEN THE `dashboard.js` SHALL menampilkan seluruh elemen kosong chart dan tidak melempar kesalahan.
11. IF elemen hook baru (`#chart-weekly`, `#daily-range`, legenda, total tengah) tidak ada, THEN THE `dashboard.js` SHALL melewati fitur terkait tanpa melempar kesalahan.
12. THE Admin_UI SHALL merender referrer teratas dan armada teratas sebagai List_Card berurutan (`<ol>`) dengan nama, hitungan, dan bilah proporsi, serta mempertahankan teks kosong "No referrer data yet." dan "No booking clicks yet.".
13. THE Admin_UI SHALL merender berita terbaru sebagai List_Card dengan tile ikon, judul tebal, dan badge tanggal, serta mempertahankan teks kosong "No news yet." dan "No login recorded.".
14. WHEN suatu ringkasan tidak memuat data pengunjung/pemesanan, THE Admin_UI SHALL merender `#dashboard-empty`, dan WHEN data ada THE Admin_UI SHALL tidak merendernya.
15. WHEN `whatsapp_configured` bernilai salah, THE Admin_UI SHALL merender `#whatsapp-warning` dengan tautan ke `admin/site-config`, dan WHEN benar THE Admin_UI SHALL tidak merendernya.
16. THE Admin_UI SHALL menyertakan tepat satu referensi `chart.umd.min.js` dari `/assets/vendor/chartjs/`.
17. THE Admin_UI SHALL menulis `#dashboard-data` sebagai JSON valid yang, setelah di-decode, setara dengan array `data` ringkasan yang diberikan.
18. WHERE preferensi Reduced_Motion aktif, THE `dashboard.js` SHALL menonaktifkan animasi Chart.js.

### Persyaratan 9: Halaman daftar, formulir, dan site config

**User Story:** Sebagai admin, saya ingin halaman kelola konten ikut bergaya baru tanpa merusak fungsi yang ada.

#### Acceptance Criteria

1. THE Admin_UI SHALL merestyle tabel daftar, formulir, tab bahasa, dan site config menggunakan Design_Tokens tanpa mengubah markup hook `admin.js`.
2. THE halaman daftar SHALL mempertahankan `.table-responsive`, `data-admin-list`, `data-entity`, `data-page`, `th[data-key]`, `#list-status`, `[data-admin-pager]`, dan `[data-page-prev|next|info]`.
3. THE formulir SHALL mempertahankan `data-lang-field`, `data-lang-tab`, `data-lang-panel`, `data-error-for`, `#form-summary`, kelas `.invalid`, dan `.field-error`.
4. THE site config SHALL mempertahankan `#config-tab-seo`, `#config-pane-seo`, dan seluruh atribut `name` field.
5. THE Admin_UI SHALL menampilkan pesan validasi dengan warna kontras minimal 4.5:1 terhadap latar.

### Persyaratan 10: Responsif

**User Story:** Sebagai pengguna di berbagai perangkat, saya ingin tampilan yang rapi dari 360 px hingga layar lebar.

#### Acceptance Criteria

1. THE Landing_Page SHALL dirancang mobile-first dengan gaya dasar untuk lebar di bawah 576 px dan penyesuaian pada breakpoint 576, 768, 992, dan 1200 px.
2. THE Landing_Page SHALL tidak menghasilkan scroll horizontal halaman pada lebar 360 px.
3. THE Stats_Row SHALL tampil satu kolom di bawah 768 px dan tiga kolom pada 768 px ke atas.
4. WHILE lebar viewport kurang dari 992 px, THE App_Frame SHALL tampil tanpa radius dan tanpa padding latar.
5. WHILE lebar viewport minimal 992 px, THE Sidebar SHALL tampil permanen di dalam App_Frame dengan posisi sticky.
6. THE Admin_UI SHALL membungkus tabel lebar dalam `.table-responsive` agar tidak melebihi viewport.

### Persyaratan 11: Aksesibilitas

**User Story:** Sebagai pengguna dengan kebutuhan aksesibilitas, saya ingin kontras, fokus, dan gerak yang nyaman.

#### Acceptance Criteria

1. THE Design_Tokens SHALL memenuhi rasio kontras minimal 4.5:1 untuk setiap pasangan latar-teks normal pada matriks kontras di design.md.
2. THE Design_Tokens SHALL memenuhi rasio kontras minimal 3:1 untuk warna fokus terhadap permukaan terang dan permukaan gelap yang dipakainya.
3. THE Landing_Page dan THE Admin_UI SHALL tidak memakai `--lp-lime` atau `--ad-green` sebagai warna teks pada latar putih.
4. WHEN elemen interaktif menerima fokus keyboard, THE Landing_Page dan THE Admin_UI SHALL menampilkan outline 3 px dengan offset 2 px berwarna `--focus-ring` (atau `--focus-ring-on-dark` pada permukaan gelap).
5. WHERE preferensi Reduced_Motion aktif, THE Landing_Page SHALL menonaktifkan smooth scroll dan transisi carousel, dan THE Admin_UI SHALL menonaktifkan transisi dan animasi chart.
6. THE Landing_Page dan THE Admin_UI SHALL menyediakan target sentuh minimal 44x44 px untuk tombol ikon, hamburger, dan tile panah berupa tautan.
7. THE Landing_Page dan THE Admin_UI SHALL merender tepat satu `h1` per halaman.
8. THE Landing_Page SHALL memungkinkan pengguna menjeda carousel otomatis lewat `[data-hero-toggle]`.
9. THE Admin_UI SHALL mempertahankan `role="img"` dan `aria-label` pada setiap canvas chart.

### Persyaratan 12: Pelestarian arsitektur dan kontrak

**User Story:** Sebagai pengembang, saya ingin redesain tidak mengubah arsitektur dan kontrak yang sudah teruji.

#### Acceptance Criteria

1. THE perubahan SHALL tidak mengubah berkas di `app/Controllers`, `app/Models`, `app/Config/Routes.php`, `app/Filters`, `app/Database`, dan `api/v1`.
2. THE perubahan SHALL mempertahankan Preserved_Hooks pada kedua layout, landing, dashboard, daftar, dan formulir.
3. THE perubahan SHALL mempertahankan seluruh tes unit yang ada tetap lulus, kecuali asersi yang diubah secara tercatat untuk target carousel `#hero-carousel`.
4. THE UI_Helper SHALL berisi hanya fungsi murni tanpa akses database, sesi, atau jaringan, kecuali pembacaan jam sistem yang diberikan sebagai argumen.
5. THE Landing_Page dan THE Admin_UI SHALL mempertahankan hanya Local_Assets dan tidak memuat `https://cdn.`, `jsdelivr`, atau `fonts.googleapis`.

### Persyaratan 13: Fungsi helper UI

**User Story:** Sebagai pengembang, saya ingin helper view yang murni dan teruji, agar logika tampilan mudah dibuktikan benar.

#### Acceptance Criteria

1. THE `ui_highlight_last_word` SHALL mengembalikan string kosong untuk input yang hanya berisi spasi.
2. WHEN input berisi satu atau lebih kata, THE `ui_highlight_last_word` SHALL mengembalikan teks ter-escape dengan kata terakhir dibungkus `<mark class="hl">`.
3. FOR ALL string input, THE `ui_highlight_last_word` SHALL menghasilkan teks yang setelah `strip_tags` dan penormalan spasi setara dengan `esc(trim(input))`.
4. FOR ALL string input, THE `ui_highlight_last_word` SHALL tidak menghasilkan tag HTML selain `<mark class="hl">` dan `</mark>`.
5. THE `ui_initials` SHALL mengembalikan 1 atau 2 huruf kapital dari kata-kata pertama nama, atau "A" bila tidak ada huruf yang dapat dipakai.
6. FOR ALL string input, THE `ui_initials` SHALL menghasilkan string tanpa karakter markup `<`, `>`, `&`, `"`, `'`.
7. THE `ui_nav_is_active` SHALL mengembalikan benar untuk `dashboard` hanya jika path permintaan sama persis dengan `dashboard`.
8. THE `ui_nav_is_active` SHALL mengembalikan benar untuk item lain jika path permintaan sama dengan path item atau diawali path item diikuti `/`.
9. THE `ui_admin_date` SHALL memformat tanggal sebagai nama hari, tanggal, nama bulan, dan tahun dalam bahasa Inggris.

### Persyaratan 14: Pengujian

**User Story:** Sebagai pengembang, saya ingin tes otomatis yang menjaga redesain tidak merusak kontrak.

#### Acceptance Criteria

1. THE rangkaian tes SHALL menyertakan tes properti Eris dengan minimal 100 iterasi untuk setiap Correctness Property di design.md, diberi tag `Feature: ui-redesign-reference, Property {n}`.
2. THE rangkaian tes SHALL memperbarui `LandingViewTest`, `DashboardViewTest`, `VendorAssetsLayoutsSmokeTest`, dan `AdminViewsTest` dengan asersi baru sesuai design.md.
3. THE rangkaian tes SHALL memverifikasi bahwa berkas `tokens.css`, `landing.css`, `admin.css`, dan berkas font woff2 ada dan tidak kosong.
4. THE rangkaian tes SHALL memverifikasi pasangan kontras token terhadap ambang pada Persyaratan 11 dengan menghitung rasio dari nilai heksadesimal di `tokens.css`.
5. THE rangkaian tes SHALL memverifikasi bahwa `ArchitectureConformanceTest` tetap lulus tanpa perubahan.
