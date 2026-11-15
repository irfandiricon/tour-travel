-- SAMPLE customer reviews (draft) to preview the "What Our Customers Say" section, in en/id/ms.
-- These are NOT real reviews. Names start with "Contoh" on purpose. Replace them with real
-- customer reviews (with the customer's permission) before publishing, or delete them.
-- Safe to re-run: a sample is skipped when its name or its Indonesian text already exists; the UPDATEs only fill missing translations.
-- Run: Get-Content database\seed_testimonials.sql -Raw | mysql -u root -h 127.0.0.1 -P 3307 tour_travel --default-character-set=utf8mb4
USE `tour_travel`;
SET NAMES utf8mb4;

INSERT INTO `c_testimonials` (`customer_name`,`photo_path`,`rating`,`review_en`,`review_id`,`review_ms`,`status`,`created_at`,`updated_at`)
SELECT * FROM (
  SELECT 'Contoh Pelanggan 1' AS customer_name, NULL AS photo_path, 5 AS rating,
         'Picked up right on time at Batam Centre ferry terminal. The driver was friendly and patient while our family group got ready. The car was clean and the air-conditioning was cold.' AS review_en,
         'Dijemput tepat waktu di Pelabuhan Batam Centre. Sopirnya ramah dan sabar menunggu rombongan keluarga kami. Mobil bersih dan AC dingin.' AS review_id,
         'Dijemput tepat pada masanya di Terminal Feri Batam Centre. Pemandunya mesra dan sabar menunggu rombongan keluarga kami. Kereta bersih dan penghawa dingin sejuk.' AS review_ms,
         'draft' AS status, NOW() AS created_at, NOW() AS updated_at
  UNION ALL
  SELECT 'Contoh Pelanggan 2', NULL, 5,
         'We rented a Hiace for 12 people to Barelang Bridge and Galang Island. The route was well planned and we could stop for photos at many spots. Recommended for groups.',
         'Sewa Hiace untuk 12 orang ke Jembatan Barelang dan Pulau Galang. Rute diatur rapi, kami bisa berhenti foto di banyak spot. Recommended untuk rombongan.',
         'Kami sewa Hiace untuk 12 orang ke Jambatan Barelang dan Pulau Galang. Laluan dirancang dengan kemas dan kami boleh berhenti bergambar di banyak tempat. Disyorkan untuk kumpulan.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Contoh Pelanggan 3', NULL, 4,
         'Booking on WhatsApp got a fast reply and the price was explained up front. The trip was comfortable, there was only a bit of traffic on the way back in the evening.',
         'Booking lewat WhatsApp cepat dibalas dan harga dijelaskan dari awal. Perjalanan nyaman, hanya sempat sedikit macet saat pulang sore.',
         'Tempahan melalui WhatsApp dibalas dengan cepat dan harga diterangkan dari awal. Perjalanan selesa, cuma sedikit sesak ketika pulang pada waktu petang.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Contoh Pelanggan 4', NULL, 5,
         'Great driver who knows Batam well. Picked us up at Harbour Bay and took us around the city. The car was clean and comfortable. Will book again.',
         'Sopir hebat yang sangat mengenal Batam. Kami dijemput di Harbour Bay lalu diajak keliling kota. Mobil bersih dan nyaman. Pasti pesan lagi.',
         'Pemandu yang hebat dan sangat mengenali Batam. Kami diambil di Harbour Bay dan dibawa bersiar-siar di sekitar bandar. Kereta bersih dan selesa. Pasti akan tempah lagi.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Contoh Pelanggan 5', NULL, 5,
         'We used the Alphard for a wedding. The car smelled fresh, and the driver was neatly dressed and on time. Our family guests were very happy.',
         'Pakai Alphard untuk acara pernikahan. Mobil wangi, sopir berpakaian rapi dan tepat waktu. Tamu keluarga kami sangat puas.',
         'Kami guna Alphard untuk majlis perkahwinan. Kereta wangi, pemandu berpakaian kemas dan tepat pada masanya. Tetamu keluarga kami sangat berpuas hati.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Contoh Pelanggan 6', NULL, 4,
         'Half-day city tour to Maha Vihara Duta Maitreya and a souvenir centre. The driver gave us many tips on where to eat. Just right for a short holiday.',
         'City tour setengah hari ke Maha Vihara Duta Maitreya dan pusat oleh-oleh. Sopir memberi banyak rekomendasi tempat makan. Pas untuk liburan singkat.',
         'Lawatan bandar separuh hari ke Maha Vihara Duta Maitreya dan pusat cenderamata. Pemandu memberi banyak cadangan tempat makan. Sesuai untuk percutian singkat.',
         'draft', NOW(), NOW()
) AS new_reviews
WHERE NOT EXISTS (
  SELECT 1 FROM `c_testimonials` t
  WHERE (t.`customer_name` = new_reviews.`customer_name` COLLATE utf8mb4_unicode_ci
         OR t.`review_id` = new_reviews.`review_id` COLLATE utf8mb4_unicode_ci)  -- sample already there under another name
    AND t.`status` <> 'deleted'
);

-- Rows created before reviews had three languages: fill in the translations.
UPDATE `c_testimonials` SET `review_en` = 'Picked up right on time at Batam Centre ferry terminal. The driver was friendly and patient while our family group got ready. The car was clean and the air-conditioning was cold.', `review_id` = 'Dijemput tepat waktu di Pelabuhan Batam Centre. Sopirnya ramah dan sabar menunggu rombongan keluarga kami. Mobil bersih dan AC dingin.', `review_ms` = 'Dijemput tepat pada masanya di Terminal Feri Batam Centre. Pemandunya mesra dan sabar menunggu rombongan keluarga kami. Kereta bersih dan penghawa dingin sejuk.', `updated_at` = NOW()
WHERE (`customer_name` = 'Contoh Pelanggan 1' OR `review_en` = 'Dijemput tepat waktu di Pelabuhan Batam Centre. Sopirnya ramah dan sabar menunggu rombongan keluarga kami. Mobil bersih dan AC dingin.') AND (`review_id` IS NULL OR `review_id` = '');
UPDATE `c_testimonials` SET `review_en` = 'We rented a Hiace for 12 people to Barelang Bridge and Galang Island. The route was well planned and we could stop for photos at many spots. Recommended for groups.', `review_id` = 'Sewa Hiace untuk 12 orang ke Jembatan Barelang dan Pulau Galang. Rute diatur rapi, kami bisa berhenti foto di banyak spot. Recommended untuk rombongan.', `review_ms` = 'Kami sewa Hiace untuk 12 orang ke Jambatan Barelang dan Pulau Galang. Laluan dirancang dengan kemas dan kami boleh berhenti bergambar di banyak tempat. Disyorkan untuk kumpulan.', `updated_at` = NOW()
WHERE (`customer_name` = 'Contoh Pelanggan 2' OR `review_en` = 'Sewa Hiace untuk 12 orang ke Jembatan Barelang dan Pulau Galang. Rute diatur rapi, kami bisa berhenti foto di banyak spot. Recommended untuk rombongan.') AND (`review_id` IS NULL OR `review_id` = '');
UPDATE `c_testimonials` SET `review_en` = 'Booking on WhatsApp got a fast reply and the price was explained up front. The trip was comfortable, there was only a bit of traffic on the way back in the evening.', `review_id` = 'Booking lewat WhatsApp cepat dibalas dan harga dijelaskan dari awal. Perjalanan nyaman, hanya sempat sedikit macet saat pulang sore.', `review_ms` = 'Tempahan melalui WhatsApp dibalas dengan cepat dan harga diterangkan dari awal. Perjalanan selesa, cuma sedikit sesak ketika pulang pada waktu petang.', `updated_at` = NOW()
WHERE (`customer_name` = 'Contoh Pelanggan 3' OR `review_en` = 'Booking lewat WhatsApp cepat dibalas dan harga dijelaskan dari awal. Perjalanan nyaman, hanya sempat sedikit macet saat pulang sore.') AND (`review_id` IS NULL OR `review_id` = '');
UPDATE `c_testimonials` SET `review_en` = 'Great driver who knows Batam well. Picked us up at Harbour Bay and took us around the city. The car was clean and comfortable. Will book again.', `review_id` = 'Sopir hebat yang sangat mengenal Batam. Kami dijemput di Harbour Bay lalu diajak keliling kota. Mobil bersih dan nyaman. Pasti pesan lagi.', `review_ms` = 'Pemandu yang hebat dan sangat mengenali Batam. Kami diambil di Harbour Bay dan dibawa bersiar-siar di sekitar bandar. Kereta bersih dan selesa. Pasti akan tempah lagi.', `updated_at` = NOW()
WHERE (`customer_name` = 'Contoh Pelanggan 4' OR `review_en` = 'Great driver who knows Batam well. Picked us up at Harbour Bay and took us around the city. The car was clean and comfortable. Will book again.') AND (`review_id` IS NULL OR `review_id` = '');
UPDATE `c_testimonials` SET `review_en` = 'We used the Alphard for a wedding. The car smelled fresh, and the driver was neatly dressed and on time. Our family guests were very happy.', `review_id` = 'Pakai Alphard untuk acara pernikahan. Mobil wangi, sopir berpakaian rapi dan tepat waktu. Tamu keluarga kami sangat puas.', `review_ms` = 'Kami guna Alphard untuk majlis perkahwinan. Kereta wangi, pemandu berpakaian kemas dan tepat pada masanya. Tetamu keluarga kami sangat berpuas hati.', `updated_at` = NOW()
WHERE (`customer_name` = 'Contoh Pelanggan 5' OR `review_en` = 'Pakai Alphard untuk acara pernikahan. Mobil wangi, sopir berpakaian rapi dan tepat waktu. Tamu keluarga kami sangat puas.') AND (`review_id` IS NULL OR `review_id` = '');
UPDATE `c_testimonials` SET `review_en` = 'Half-day city tour to Maha Vihara Duta Maitreya and a souvenir centre. The driver gave us many tips on where to eat. Just right for a short holiday.', `review_id` = 'City tour setengah hari ke Maha Vihara Duta Maitreya dan pusat oleh-oleh. Sopir memberi banyak rekomendasi tempat makan. Pas untuk liburan singkat.', `review_ms` = 'Lawatan bandar separuh hari ke Maha Vihara Duta Maitreya dan pusat cenderamata. Pemandu memberi banyak cadangan tempat makan. Sesuai untuk percutian singkat.', `updated_at` = NOW()
WHERE (`customer_name` = 'Contoh Pelanggan 6' OR `review_en` = 'City tour setengah hari ke Maha Vihara Duta Maitreya dan pusat oleh-oleh. Sopir memberi banyak rekomendasi tempat makan. Pas untuk liburan singkat.') AND (`review_id` IS NULL OR `review_id` = '');