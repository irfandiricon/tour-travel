-- 6 item gallery (published) dari gambar di public/uploads/gallery.
-- Aman dijalankan ulang: baris dengan image_path yang sama dilewati.
-- Jalankan: Get-Content database\seed_gallery.sql -Raw | mysql -u root -h 127.0.0.1 -P 3307 --default-character-set=utf8mb4
USE `tour_travel`;
SET NAMES utf8mb4;

INSERT INTO `c_gallery` (`caption_en`,`caption_id`,`caption_ms`,`image_path`,`sort_order`,`status`,`created_at`,`updated_at`)
SELECT * FROM (
  SELECT 'Coastal road trip in a white Toyota' AS caption_en, 'Perjalanan menyusuri pantai dengan Toyota putih' AS caption_id, 'Perjalanan di tepi pantai dengan Toyota putih' AS caption_ms,
         'uploads/gallery/coastal-road-trip-white-toyota.png' AS image_path, 1 AS sort_order, 'published' AS status, NOW() AS created_at, NOW() AS updated_at
  UNION ALL
  SELECT 'Mountain roadside adventure with a Toyota SUV', 'Petualangan di jalur pegunungan dengan SUV Toyota', 'Pengembaraan di tepi jalan pergunungan dengan SUV Toyota',
         'uploads/gallery/mountain-roadside-toyota-suv.png', 2, 'published', NOW(), NOW()
  UNION ALL
  SELECT 'Arriving at a tropical resort in a luxury MPV', 'Tiba di resor tropis dengan MPV mewah', 'Tiba di resort tropika dengan MPV mewah',
         'uploads/gallery/tropical-resort-arrival-luxury-mpv.png', 3, 'published', NOW(), NOW()
  UNION ALL
  SELECT 'Silver van at a modern city plaza', 'Van perak di plaza kota modern', 'Van perak di plaza bandar moden',
         'uploads/gallery/silver-van-modern-city-plaza.png', 4, 'published', NOW(), NOW()
  UNION ALL
  SELECT 'Smiling tourists by the mountain lake', 'Wisatawan tersenyum di tepi danau pegunungan', 'Pelancong tersenyum di tepi tasik pergunungan',
         'uploads/gallery/smiling-tourists-mountain-lake.png', 5, 'published', NOW(), NOW()
  UNION ALL
  SELECT 'Golden-hour coastal road trip', 'Perjalanan pantai saat golden hour', 'Perjalanan tepi pantai ketika golden hour',
         'uploads/gallery/golden-hour-coastal-road-trip.png', 6, 'published', NOW(), NOW()
) AS new_gallery
WHERE NOT EXISTS (
  SELECT 1 FROM `c_gallery` g
  WHERE g.`image_path` = new_gallery.`image_path` COLLATE utf8mb4_unicode_ci AND g.`status` <> 'deleted'
);