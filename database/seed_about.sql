-- Konten bagian About Us (heading, isi 3 paragraf, gambar). Aman dijalankan ulang (UPDATE).
-- Jalankan: Get-Content database\seed_about.sql -Raw | mysql -u root -h 127.0.0.1 -P 3307 --default-character-set=utf8mb4
USE `tour_travel`;
SET NAMES utf8mb4;

UPDATE `site_config` SET
  `about_title_en` = 'Comfortable Journeys, More Memorable Holidays',
  `about_title_id` = 'Perjalanan Nyaman, Liburan Lebih Berkesan',
  `about_title_ms` = 'Perjalanan Selesa, Percutian Lebih Bermakna',
  `about_body_en`  = 'Kimigo Travel is a travel transportation and vehicle rental service in Batam, here to accompany all kinds of journeys, from airport transfers and city tours to family holidays and group trips.\n\nOur fleet covers every need: comfortable MPVs for families, the premium Alphard for VIP guests, Hiace vans for groups, and medium buses for large parties. Every vehicle is regularly serviced and driven by friendly drivers.\n\nBooking is easy: choose your vehicle, chat with us on WhatsApp, and we will arrange the rest. Flexible rental plans fit your schedule, so you can focus on enjoying your holiday.',
  `about_body_id`  = 'Kimigo Travel hadir sebagai layanan transportasi wisata dan rental kendaraan di Batam untuk menemani berbagai kebutuhan perjalanan Anda, mulai dari antar-jemput bandara dan city tour hingga liburan keluarga dan perjalanan rombongan.\n\nArmada kami beragam: MPV nyaman untuk keluarga, Alphard premium untuk tamu VIP, Hiace untuk rombongan, hingga bus medium untuk kelompok besar. Setiap kendaraan rutin diservis dan dikemudikan oleh sopir yang ramah.\n\nPemesanan mudah: pilih kendaraan, hubungi kami lewat WhatsApp, dan kami yang mengatur sisanya. Paket sewa yang fleksibel menyesuaikan jadwal Anda, sehingga Anda bisa fokus menikmati liburan.',
  `about_body_ms`  = 'Kimigo Travel ialah perkhidmatan pengangkutan pelancongan dan sewaan kenderaan di Batam untuk menemani pelbagai keperluan perjalanan anda, daripada pengangkutan lapangan terbang dan lawatan bandar hingga percutian keluarga dan perjalanan berkumpulan.\n\nArmada kami pelbagai: MPV selesa untuk keluarga, Alphard premium untuk tetamu VIP, van Hiace untuk kumpulan, hingga bas sederhana untuk rombongan besar. Setiap kenderaan diservis secara berkala dan dipandu oleh pemandu yang mesra.\n\nTempahan mudah: pilih kenderaan, hubungi kami melalui WhatsApp, dan kami uruskan selebihnya. Pelan sewaan yang fleksibel menyesuaikan jadual anda, supaya anda boleh menikmati percutian dengan tenang.',
  `about_image`    = 'uploads/about/scenic-island-road-trip-couple.png',
  `updated_at`     = NOW()
ORDER BY `id` ASC
LIMIT 1;