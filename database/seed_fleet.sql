-- Contoh 5 armada (status draft). Aman dijalankan ulang: baris dengan name_en yang sama dilewati.
-- Jalankan: mysql -u root -h 127.0.0.1 -P 3307 --default-character-set=utf8mb4 < database/seed_fleet.sql
USE `tour_travel`;
SET NAMES utf8mb4;

INSERT INTO `c_fleet` (`name_en`,`name_id`,`name_ms`,`description_en`,`description_id`,`description_ms`,`image_path`,`status`,`created_at`,`updated_at`)
SELECT * FROM (
  SELECT 'Toyota Avanza' AS name_en, 'Toyota Avanza' AS name_id, 'Toyota Avanza' AS name_ms,
         '7-seat MPV that is fuel-efficient and comfortable, ideal for families and small groups. Air-conditioned and perfect for city trips and airport transfers.' AS description_en,
         'MPV 7 kursi yang irit dan nyaman, cocok untuk keluarga dan grup kecil. Ber-AC dan pas untuk keliling kota maupun antar-jemput bandara.' AS description_id,
         'MPV 7 tempat duduk yang jimat dan selesa, sesuai untuk keluarga dan kumpulan kecil. Berhawa dingin dan sesuai untuk lawatan bandar serta pengangkutan lapangan terbang.' AS description_ms,
         NULL AS image_path, 'draft' AS status, NOW() AS created_at, NOW() AS updated_at
  UNION ALL
  SELECT 'Toyota Kijang Innova Reborn','Toyota Kijang Innova Reborn','Toyota Kijang Innova Reborn',
         'Spacious 7-seat MPV with a smooth ride and generous legroom. A reliable choice for longer trips, island tours and business travel.',
         'MPV 7 kursi yang lega dengan kenyamanan berkendara terbaik di kelasnya. Pilihan andal untuk perjalanan jauh, tur pulau, dan perjalanan bisnis.',
         'MPV 7 tempat duduk yang luas dengan perjalanan yang lancar dan ruang kaki selesa. Pilihan boleh dipercayai untuk perjalanan jauh, lawatan pulau dan perjalanan perniagaan.',
         NULL, 'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Toyota Alphard (Premium)','Toyota Alphard (Premium)','Toyota Alphard (Premium)',
         'Premium MPV with plush captain seats and a quiet cabin. Ideal for VIP guests, executive transfers, weddings and special occasions.',
         'MPV premium dengan kursi captain empuk dan kabin senyap. Cocok untuk tamu VIP, antar-jemput eksekutif, pernikahan, dan acara spesial.',
         'MPV premium dengan tempat duduk kapten yang empuk dan kabin senyap. Sesuai untuk tetamu VIP, pengangkutan eksekutif, majlis perkahwinan dan acara istimewa.',
         NULL, 'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Toyota Hiace Commuter','Toyota Hiace Commuter','Toyota Hiace Commuter',
         'Spacious van for up to 14 passengers with strong air-conditioning and ample luggage space. Great for group tours, family gatherings and airport transfers.',
         'Van lega untuk hingga 14 penumpang dengan AC dingin dan bagasi luas. Pas untuk tur rombongan, acara keluarga, dan antar-jemput bandara.',
         'Van luas untuk sehingga 14 penumpang dengan penghawa dingin yang kuat dan ruang bagasi yang besar. Sesuai untuk lawatan berkumpulan, perhimpunan keluarga dan pengangkutan lapangan terbang.',
         NULL, 'draft', NOW(), NOW()
  UNION ALL
  SELECT CONCAT('Medium Bus (25', CONVERT(UNHEX('E28093') USING utf8mb4), '35 Seats)'),CONCAT('Bus Medium (25', CONVERT(UNHEX('E28093') USING utf8mb4), '35 Kursi)'),CONCAT('Bas Sederhana (25', CONVERT(UNHEX('E28093') USING utf8mb4), '35 Tempat Duduk)'),
         'Comfortable air-conditioned bus for large groups such as company outings, school trips and tour groups. Reclining seats with space for luggage.',
         'Bus ber-AC yang nyaman untuk rombongan besar seperti gathering kantor, study tour, dan wisata grup. Kursi reclining dengan bagasi luas.',
         'Bas berhawa dingin yang selesa untuk kumpulan besar seperti lawatan syarikat, lawatan sekolah dan kumpulan pelancong. Tempat duduk boleh sandar dengan ruang bagasi.',
         NULL, 'draft', NOW(), NOW()
) AS new_fleet
WHERE NOT EXISTS (
  SELECT 1 FROM `c_fleet` f WHERE f.`name_en` = new_fleet.`name_en` COLLATE utf8mb4_unicode_ci AND f.`status` <> 'deleted'
);