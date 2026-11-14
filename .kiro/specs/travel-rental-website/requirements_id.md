# Dokumen Kebutuhan

## Pendahuluan

Travel Rental Website adalah aplikasi web publik untuk layanan perjalanan dan penyewaan kendaraan yang dibangun menggunakan CodeIgniter 4.7.x pada PHP 8.5.x. Produk ini terdiri atas dua antarmuka: satu Landing Page publik yang menampilkan bisnis penyewaan (armada, informasi, berita, galeri, FAQ, dan kontak), serta CMS Admin yang memungkinkan administrator membuat konten Informasi dan Berita, mengatur konten Landing Page, dan mengelola konfigurasi sistem.

Landing Page mendukung tiga bahasa—Inggris (bawaan), Indonesia, dan Melayu—dengan konten ditampilkan sesuai bahasa yang dipilih pengunjung. Pemesanan tidak diproses di dalam aplikasi; sebagai gantinya, pengunjung diarahkan ke nomor WhatsApp yang dapat dikonfigurasi, tempat pihak bisnis melanjutkan percakapan pemesanan. Nomor WhatsApp dikelola melalui konfigurasi CMS Admin.

Aplikasi mengikuti pola CodeIgniter 4 pada workspace: controller halaman publik dan controller Admin mewarisi `BaseController`, sedangkan kelas `Api*Controller` mewarisi `Controller` dan bertindak sebagai lapisan proxy yang memanggil endpoint `api/v1/*` melalui `Service::ApiRequestWithHeaderToken()`.

## Glosarium

- **Landing_Page**: Satu halaman publik yang menampilkan konten bisnis penyewaan kepada pengunjung.
- **CMS_Admin**: Antarmuka administrasi yang memerlukan autentikasi untuk mengelola konten, konfigurasi, dan Landing Page.
- **Content_Manager**: Administrator terautentikasi yang masuk ke CMS_Admin untuk mengelola konten dan konfigurasi.
- **Visitor**: Pengguna publik tanpa autentikasi yang menjelajahi Landing_Page.
- **Information_Item**: Data konten informasi statis (misalnya Tentang Kami, Ketentuan, dan deskripsi layanan) yang dibuat melalui CMS_Admin dan ditampilkan pada Landing_Page.
- **News_Item**: Data artikel yang diurutkan berdasarkan waktu, dibuat melalui CMS_Admin dan ditampilkan pada Landing_Page.
- **Fleet_Item**: Data kendaraan (mobil, bus, atau kendaraan lain) yang ditampilkan pada bagian armada di Landing_Page.
- **FAQ_Item**: Data pertanyaan dan jawaban yang ditampilkan pada Landing_Page.
- **Supported_Language**: Salah satu dari tiga bahasa yang didukung sistem: Inggris (`en`), Indonesia (`id`), Melayu (`ms`).
- **Default_Language**: Bahasa Inggris (`en`), yang digunakan ketika belum ada bahasa yang dipilih atau terjemahan yang diminta tidak tersedia.
- **Language_Selector**: Kontrol pada Landing_Page yang memungkinkan Visitor memilih Supported_Language.
- **WhatsApp_Number**: Nomor telepon yang dapat dikonfigurasi melalui CMS_Admin untuk memulai percakapan pemesanan di WhatsApp.
- **WhatsApp_Booking_Link**: URL klik-untuk-chat WhatsApp yang dibuat berdasarkan WhatsApp_Number yang dikonfigurasi.
- **Site_Configuration**: Kumpulan pengaturan Landing_Page yang dapat dikelola melalui CMS_Admin (misalnya WhatsApp_Number, detail kontak, dan konten bagian halaman).
- **Page_Controller**: Controller publik yang mewarisi `BaseController` dan merender tampilan Landing_Page.
- **Admin_Controller**: Controller CMS_Admin terautentikasi yang mewarisi `BaseController`.
- **Api_Controller**: `Api*Controller` yang mewarisi `Controller` dan meneruskan permintaan ke endpoint `api/v1/*` melalui `Service::ApiRequestWithHeaderToken()`.

## Kebutuhan Sistem

### Kebutuhan 1: Tampilan Landing Page

**Cerita Pengguna:** Sebagai Visitor, saya ingin melihat landing page bisnis penyewaan agar dapat mengetahui armada, layanan, dan cara melakukan pemesanan.

#### Kriteria Penerimaan

1. KETIKA Visitor mengakses Landing_Page, Page_Controller HARUS merender Landing_Page dengan bagian hero, tentang kami, armada, galeri, FAQ, dan kontak, masing-masing diisi dari Site_Configuration dan konten yang telah dipublikasikan, dalam waktu maksimal 3 detik pada beban normal.
2. Page_Controller HARUS merender konten Landing_Page dalam Supported_Language yang sedang dipilih Visitor, yaitu EN, ID, atau MS.
3. KETIKA Visitor belum memilih Supported_Language, Page_Controller HARUS merender Landing_Page dalam Default_Language (EN).
4. KETIKA Visitor mengakses Landing_Page, Page_Controller HARUS hanya menampilkan Information_Item, News_Item, dan Fleet_Item dengan status `published`, serta mengecualikan semua item dengan status lainnya.
5. JIKA Fleet_Item memiliki gambar terkait, Page_Controller HARUS menampilkan gambar tersebut pada bagian armada.
6. JIKA Fleet_Item tidak memiliki gambar terkait, Page_Controller HARUS menampilkan gambar pengganti (*placeholder*) pada bagian armada dan tetap merender Fleet_Item lainnya.
7. JIKA Site_Configuration tidak dapat diambil atau mengembalikan data kosong untuk bagian yang diperlukan, Page_Controller HARUS merender Landing_Page tanpa bagian tersebut dan menampilkan keterangan bahwa konten bagian tersebut tidak tersedia, tanpa menyebabkan kegagalan perenderan seluruh halaman.
8. KETIKA Visitor mengakses Landing_Page dan tidak terdapat Information_Item, News_Item, atau Fleet_Item yang dipublikasikan untuk suatu bagian, Page_Controller HARUS merender bagian tersebut dengan indikator keadaan kosong dan tetap merender semua bagian lainnya yang memiliki konten.

### Kebutuhan 2: Dukungan Multibahasa

**Cerita Pengguna:** Sebagai Visitor, saya ingin mengganti bahasa situs antara Inggris, Indonesia, dan Melayu agar dapat membaca konten dalam bahasa pilihan saya.

#### Kriteria Penerimaan

1. Landing_Page HARUS menyediakan Language_Selector dengan tepat tiga pilihan Supported_Language: Inggris (`en`), Indonesia (`id`), dan Melayu (`ms`).
2. KETIKA Visitor membuka Landing_Page tanpa pilihan bahasa yang sebelumnya tersimpan dalam sesi saat ini, Page_Controller HARUS merender Landing_Page dalam Default_Language (`en`).
3. KETIKA Visitor memilih Supported_Language melalui Language_Selector, Page_Controller HARUS merender Landing_Page dalam bahasa yang dipilih.
4. KETIKA Visitor memilih Supported_Language melalui Language_Selector, Page_Controller HARUS menyimpan pilihan tersebut selama sesi berjalan agar permintaan berikutnya dalam sesi yang sama menggunakan bahasa tersebut tanpa perlu memilih ulang.
5. JIKA permintaan menentukan nilai bahasa yang bukan salah satu Supported_Language (`en`, `id`, `ms`), Page_Controller HARUS merender Landing_Page dalam Default_Language (`en`) dan TIDAK BOLEH mengubah pilihan bahasa yang telah tersimpan.
6. JIKA suatu kolom konten tidak memiliki terjemahan dalam Supported_Language yang dipilih, Page_Controller HARUS menampilkan nilai dalam Default_Language (`en`) untuk kolom tersebut.
7. JIKA suatu kolom konten tidak memiliki terjemahan dalam Supported_Language yang dipilih DAN juga tidak memiliki nilai Default_Language (`en`), Page_Controller HARUS menampilkan string kosong untuk kolom tersebut dan merender kolom lainnya tanpa kesalahan.
8. CMS_Admin HARUS memungkinkan Content_Manager memasukkan nilai dengan panjang maksimal 5.000 karakter per kolom untuk setiap kolom konten yang dapat diterjemahkan dalam bahasa Inggris (`en`), Indonesia (`id`), dan Melayu (`ms`).
9. JIKA Content_Manager menyimpan data konten tanpa nilai bahasa Inggris (`en`) pada kolom yang dapat diterjemahkan, Admin_Controller HARUS menolak penyimpanan, mempertahankan data sebelumnya tanpa perubahan, dan mengembalikan pesan validasi yang mengidentifikasi kolom Default_Language yang belum diisi.

### Kebutuhan 3: Autentikasi CMS Admin

**Cerita Pengguna:** Sebagai Content_Manager, saya ingin masuk ke CMS Admin agar hanya administrator yang berwenang yang dapat mengelola konten dan konfigurasi.

#### Kriteria Penerimaan

1. KETIKA pengguna yang belum terautentikasi mengakses halaman CMS_Admin selain halaman login, Admin_Controller HARUS mengarahkan pengguna ke halaman login CMS_Admin tanpa menampilkan konten halaman yang diminta.
2. KETIKA pengguna mengirimkan kredensial yang cocok dengan akun Content_Manager aktif melalui halaman login CMS_Admin, Admin_Controller HARUS membuat sesi terautentikasi yang berakhir setelah 30 menit tidak aktif dan mengarahkan pengguna ke dashboard CMS_Admin.
3. JIKA pengguna mengirimkan formulir login CMS_Admin dengan nama pengguna atau kata sandi kosong atau tidak cocok dengan akun Content_Manager aktif, Admin_Controller HARUS menolak percobaan login, membiarkan sesi tidak terautentikasi, dan menampilkan pesan kesalahan autentikasi tanpa mengungkap kolom mana yang salah.
4. KETIKA Content_Manager yang telah terautentikasi meminta logout, Admin_Controller HARUS mengakhiri sesi terautentikasi dan mengarahkan pengguna ke halaman login CMS_Admin.
5. JIKA pengguna melakukan 5 kali atau lebih percobaan login tidak valid secara berturut-turut untuk nama pengguna yang sama dalam 15 menit, Admin_Controller HARUS menolak percobaan login berikutnya untuk nama pengguna tersebut selama 15 menit dan menampilkan pesan bahwa akun terkunci sementara.
6. SELAMA sesi terautentikasi tidak aktif selama 30 menit atau lebih, Admin_Controller HARUS memperlakukan sesi sebagai tidak terautentikasi dan mengharuskan Content_Manager login kembali.

### Kebutuhan 4: Pengelolaan Konten Informasi

**Cerita Pengguna:** Sebagai Content_Manager, saya ingin membuat, mengedit, dan menghapus konten Informasi agar Landing Page menampilkan informasi bisnis yang akurat.

#### Kriteria Penerimaan

1. KETIKA Content_Manager mengirimkan Information_Item baru dengan seluruh kolom wajib terisi dan setiap kolom teks yang dapat diterjemahkan (`en`, `id`, `ms`) berisi 1–5.000 karakter, Api_Controller HARUS membuat Information_Item dan mengembalikan respons berhasil dengan status 200 serta ID Information_Item yang dibuat.
2. KETIKA Content_Manager meminta daftar Information_Item, Api_Controller HARUS mengembalikan daftar tersebut beserta metadata paginasi yang mencakup jumlah total, nomor halaman saat ini, dan ukuran halaman antara 1–100 item per halaman dengan nilai bawaan 10.
3. KETIKA Content_Manager mengirimkan perubahan terhadap Information_Item yang sudah ada dengan seluruh kolom wajib terisi dan setiap kolom teks yang dapat diterjemahkan (`en`, `id`, `ms`) berisi 1–5.000 karakter, Api_Controller HARUS memperbarui Information_Item dan mengembalikan respons berhasil dengan status 200.
4. KETIKA Content_Manager meminta penghapusan Information_Item yang sudah ada, Api_Controller HARUS menghapus Information_Item dan mengembalikan respons berhasil dengan status 200.
5. JIKA Content_Manager mengirimkan Information_Item dengan satu atau lebih kolom wajib yang belum diisi, Api_Controller HARUS menolak permintaan tanpa menyimpan perubahan apa pun dan mengembalikan kesalahan validasi dengan status 422 yang menyebutkan nama setiap kolom yang belum diisi.
6. JIKA Content_Manager mengirimkan permintaan pembuatan atau pengeditan yang merujuk pada ID Information_Item yang tidak ada, Api_Controller HARUS menolak permintaan tanpa menyimpan perubahan apa pun dan mengembalikan respons kesalahan dengan status 404 yang menyatakan Information_Item tidak ditemukan.
7. JIKA permintaan Api_Controller ke `api/v1/*` gagal atau tidak menghasilkan respons, Api_Controller HARUS mengembalikan respons kesalahan dengan status selain 200 dan pesan yang menyatakan permintaan ke layanan upstream gagal, tanpa melaporkan operasi sebagai berhasil.
8. KETIKA Content_Manager mengubah status Information_Item menjadi `published`, Api_Controller HARUS menandai Information_Item agar terlihat di Landing_Page dalam ketiga terjemahan (`en`, `id`, `ms`) dan mengembalikan respons berhasil dengan status 200.

### Kebutuhan 5: Pengelolaan Konten Berita

**Cerita Pengguna:** Sebagai Content_Manager, saya ingin membuat, mengedit, dan menghapus konten Berita agar pengunjung dapat membaca pengumuman dan artikel terbaru.

#### Kriteria Penerimaan

1. KETIKA Content_Manager mengirimkan News_Item baru dengan judul sepanjang 1–200 karakter dan isi sepanjang 1–20.000 karakter dalam masing-masing dari tiga terjemahan (`en`, `id`, `ms`), Api_Controller HARUS membuat News_Item dengan tanggal publikasi dan mengembalikan respons berhasil dengan status 200.
2. KETIKA Content_Manager meminta daftar News_Item, Api_Controller HARUS mengembalikan daftar yang diurutkan berdasarkan tanggal publikasi secara menurun (terbaru terlebih dahulu), beserta metadata paginasi yang mencakup jumlah total, nomor halaman saat ini (minimal 1), dan ukuran halaman antara 1–100 dengan nilai bawaan 10.
3. KETIKA Content_Manager mengirimkan perubahan pada News_Item yang sudah ada dengan judul sepanjang 1–200 karakter dan isi sepanjang 1–20.000 karakter dalam masing-masing dari tiga terjemahan (`en`, `id`, `ms`), Api_Controller HARUS memperbarui News_Item dan mengembalikan respons berhasil dengan status 200.
4. KETIKA Content_Manager meminta penghapusan News_Item yang sudah ada, Api_Controller HARUS menghapus News_Item dan mengembalikan respons berhasil dengan status 200.
5. JIKA Content_Manager mengirimkan News_Item dengan satu atau lebih kolom wajib yang kosong atau melebihi batas ukuran, Api_Controller HARUS menolak permintaan tanpa menyimpan perubahan apa pun dan mengembalikan kesalahan validasi dengan status 422 yang menyebutkan nama setiap kolom yang tidak valid.
6. JIKA Content_Manager mengirimkan permintaan pengeditan atau penghapusan yang merujuk pada ID News_Item yang tidak ada, Api_Controller HARUS menolak permintaan tanpa menyimpan perubahan apa pun dan mengembalikan respons kesalahan dengan status 404 yang menyatakan News_Item tidak ditemukan.
7. JIKA permintaan Api_Controller ke `api/v1/*` gagal atau tidak menghasilkan respons, Api_Controller HARUS mengembalikan respons kesalahan dengan status selain 200 dan pesan yang menyatakan permintaan ke layanan upstream gagal, tanpa melaporkan operasi sebagai berhasil.
8. KETIKA Visitor mengakses Landing_Page, Page_Controller HARUS menampilkan News_Item yang telah dipublikasikan (dengan tanggal publikasi kurang dari atau sama dengan waktu saat ini), diurutkan berdasarkan tanggal publikasi secara menurun.
9. KETIKA Visitor mengakses Landing_Page dan tidak ada News_Item yang telah dipublikasikan, Page_Controller HARUS merender bagian berita dengan indikator keadaan kosong.

### Kebutuhan 6: Konfigurasi Konten Landing Page

**Cerita Pengguna:** Sebagai Content_Manager, saya ingin mengonfigurasi bagian-bagian Landing Page dan armada agar dapat mengontrol apa yang dilihat pengunjung tanpa mengubah kode.

#### Kriteria Penerimaan

1. KETIKA Content_Manager menyimpan Site_Configuration dengan seluruh kolom wajib terisi dan valid, Api_Controller HARUS menyimpan Site_Configuration dan mengembalikan respons berhasil dalam waktu maksimal 5 detik.
2. JIKA Content_Manager menyimpan Site_Configuration dengan satu atau lebih kolom wajib yang kosong atau tidak valid, Api_Controller HARUS menolak permintaan, mempertahankan Site_Configuration yang sebelumnya tersimpan tanpa perubahan, dan mengembalikan kesalahan validasi yang mengidentifikasi setiap kolom tidak valid.
3. KETIKA Content_Manager memperbarui konten bagian hero, tentang kami, galeri, atau kontak, Api_Controller HARUS menyimpan konten bagian yang diperbarui untuk masing-masing dari tiga Supported_Language (`en`, `id`, `ms`).
4. JIKA Content_Manager memperbarui konten bagian halaman untuk kurang dari ketiga Supported_Language (`en`, `id`, `ms`), Api_Controller HARUS menolak permintaan dan mengembalikan kesalahan validasi yang mengidentifikasi setiap Supported_Language dengan konten yang belum tersedia.
5. KETIKA Content_Manager mengirimkan Fleet_Item baru dengan seluruh kolom wajib terisi dan valid, Api_Controller HARUS membuat Fleet_Item dan mengembalikan respons berhasil dalam waktu maksimal 5 detik.
6. KETIKA Content_Manager mengirimkan FAQ_Item baru dengan seluruh kolom wajib terisi dan valid, Api_Controller HARUS membuat FAQ_Item dan mengembalikan respons berhasil dalam waktu maksimal 5 detik.
7. KETIKA Content_Manager mengunggah gambar dengan tipe yang diizinkan (JPEG, PNG, atau WebP) dan ukuran tidak lebih dari 5 megabita untuk Fleet_Item atau galeri, Api_Controller HARUS menyimpan gambar dan mengaitkan referensi gambar yang tersimpan dengan data terkait.
8. JIKA Content_Manager mengunggah berkas dengan tipe yang tidak diizinkan (selain JPEG, PNG, atau WebP), Api_Controller HARUS menolak unggahan, mempertahankan data yang sudah ada tanpa perubahan, dan mengembalikan kesalahan validasi yang menyebutkan tipe gambar yang diizinkan.
9. JIKA Content_Manager mengunggah berkas gambar berukuran lebih dari 5 megabita, Api_Controller HARUS menolak unggahan, mempertahankan data yang sudah ada tanpa perubahan, dan mengembalikan kesalahan validasi yang menyebutkan batas ukuran maksimal 5 megabita.

### Kebutuhan 7: Konfigurasi dan Alur Pemesanan WhatsApp

**Cerita Pengguna:** Sebagai Content_Manager, saya ingin mengonfigurasi nomor WhatsApp untuk pemesanan agar permintaan pemesanan pengunjung diterima oleh kontak bisnis yang benar.

#### Kriteria Penerimaan

1. CMS_Admin HARUS menyediakan kolom konfigurasi WhatsApp_Number dalam Site_Configuration yang menerima 8–15 digit dalam format internasional E.164 dengan tanda plus di awal dan kode negara.
2. KETIKA Content_Manager menyimpan WhatsApp_Number yang berisi 8–15 digit dalam format internasional E.164, Api_Controller HARUS menyimpan WhatsApp_Number dan mengembalikan respons berhasil yang menunjukkan penyimpanan sukses.
3. JIKA Content_Manager menyimpan WhatsApp_Number yang tidak sesuai format internasional E.164 (tidak memiliki kode negara, mengandung karakter selain angka kecuali satu tanda plus di awal, atau memiliki kurang dari 8 atau lebih dari 15 digit), Api_Controller HARUS menolak penyimpanan, mempertahankan WhatsApp_Number yang sebelumnya tersimpan tanpa perubahan, dan mengembalikan kesalahan validasi yang menjelaskan format internasional E.164 yang diwajibkan.
4. KETIKA Content_Manager menyimpan Site_Configuration dengan kolom WhatsApp_Number kosong, Api_Controller HARUS menyimpan WhatsApp_Number kosong dan mengembalikan respons berhasil yang menunjukkan bahwa fitur pemesanan dinonaktifkan.
5. KETIKA Visitor mengaktifkan tindakan pemesanan pada Landing_Page dan WhatsApp_Number yang valid telah dikonfigurasi, Page_Controller HARUS membuka WhatsApp_Booking_Link (tautan klik-untuk-chat `wa.me`) yang dibuat dari WhatsApp_Number yang dikonfigurasi dalam waktu maksimal 2 detik.
6. KETIKA tindakan pemesanan pada Fleet_Item diaktifkan, Page_Controller HARUS menyertakan nama Fleet_Item, dibatasi maksimal 200 karakter, dalam pesan yang terisi otomatis pada WhatsApp_Booking_Link.
7. JIKA tidak ada WhatsApp_Number yang dikonfigurasi dalam Site_Configuration atau WhatsApp_Number yang tersimpan kosong, Page_Controller HARUS menyembunyikan tindakan pemesanan dari Landing_Page.

### Kebutuhan 8: Tampilan dan Pengelolaan FAQ

**Cerita Pengguna:** Sebagai Content_Manager, saya ingin mengelola entri FAQ agar pengunjung dapat menemukan jawaban cepat untuk pertanyaan umum.

#### Kriteria Penerimaan

1. KETIKA Content_Manager mengirimkan FAQ_Item baru dengan pertanyaan sepanjang 1–500 karakter dan jawaban sepanjang 1–5.000 karakter dalam setiap Supported_Language (`en`, `id`, `ms`), Api_Controller HARUS membuat FAQ_Item dan mengembalikan respons berhasil dengan status 200.
2. KETIKA Content_Manager meminta penghapusan FAQ_Item yang sudah ada, Api_Controller HARUS menghapus FAQ_Item dan mengembalikan respons berhasil dengan status 200.
3. JIKA Content_Manager meminta penghapusan ID FAQ_Item yang tidak ada, Api_Controller HARUS mengembalikan respons kesalahan dengan status 404 yang menyatakan FAQ_Item tidak ditemukan.
4. KETIKA Visitor mengakses Landing_Page, Page_Controller HARUS menampilkan FAQ_Item yang telah dipublikasikan dalam Supported_Language yang dipilih, dengan menggunakan nilai Default_Language (`en`) sebagai cadangan untuk setiap kolom yang tidak memiliki terjemahan.
5. KETIKA Visitor mengakses Landing_Page dan tidak ada FAQ_Item yang telah dipublikasikan, Page_Controller HARUS merender bagian FAQ dengan indikator keadaan kosong.
6. JIKA Content_Manager mengirimkan FAQ_Item dengan pertanyaan kosong atau jawaban kosong dalam Default_Language (`en`), Api_Controller HARUS menolak permintaan tanpa menyimpan perubahan apa pun dan mengembalikan kesalahan validasi yang mengidentifikasi kolom yang belum diisi.

### Kebutuhan 9: Kesesuaian Arsitektur

**Cerita Pengguna:** Sebagai pengembang, saya ingin implementasi mengikuti pola CodeIgniter 4 pada workspace agar basis kode tetap konsisten dan mudah dipelihara.

#### Kriteria Penerimaan

1. Kelas Api_Controller HARUS mewarisi `Controller` dan TIDAK BOLEH mewarisi `BaseController`, serta HARUS meneruskan operasi konten ke endpoint `api/v1/*` melalui `Service::ApiRequestWithHeaderToken()`.
2. Kelas Page_Controller dan Admin_Controller HARUS mewarisi `BaseController`.
3. KETIKA metode Api_Controller dipanggil melalui AJAX, Api_Controller HARUS mengembalikan respons JSON yang berisi kolom `status`, `message`, `data`, dan `total`.
4. KETIKA metode Api_Controller dipanggil dari PHP Admin_Controller (bukan melalui AJAX), Api_Controller HARUS mengembalikan sebuah array.
5. JIKA permintaan proxy Api_Controller melalui `Service::ApiRequestWithHeaderToken()` gagal atau mengembalikan respons null/tidak valid, Api_Controller HARUS menerapkan mekanisme fallback yang aman terhadap null dan mengembalikan respons dengan status selain 200 serta pesan kesalahan, bukan melempar kesalahan yang tidak ditangani.
6. Kelas Api_Controller dan Admin_Controller HARUS mendeklarasikan setiap properti yang diberi nilai di dalam constructor.
7. Kelas Admin_Controller TIDAK BOLEH menduplikasi inisialisasi `authCheck`, `menu`, `service`, atau `user` yang telah disediakan oleh `BaseController::initController()`.
