-- FAQ content (draft), trilingual en/id/ms. Safe to re-run: rows with the same question_en are skipped.
-- Answers only repeat what the site already states (WhatsApp booking, vehicles with a driver, airport
-- transfers, city tours, fleet types). Prices and change/cancel terms are confirmed per booking.
-- Run: Get-Content database\seed_faq.sql -Raw | mysql -u root -h 127.0.0.1 -P 3307 tour_travel --default-character-set=utf8mb4
USE `tour_travel`;
SET NAMES utf8mb4;

INSERT INTO `c_faqs` (`question_en`,`question_id`,`question_ms`,`answer_en`,`answer_id`,`answer_ms`,`status`,`created_at`,`updated_at`)
SELECT * FROM (
  SELECT 'How do I book a vehicle?' AS question_en,
         'Bagaimana cara memesan kendaraan?' AS question_id,
         'Bagaimana cara menempah kenderaan?' AS question_ms,
         'Choose a vehicle in Our Fleet and tap "Book via WhatsApp". Send us your travel date, pickup point, destination and number of passengers. We will confirm availability and send you the price before you confirm the booking.' AS answer_en,
         'Pilih kendaraan di bagian Armada Kami, lalu tekan "Pesan via WhatsApp". Kirimkan tanggal perjalanan, lokasi penjemputan, tujuan, dan jumlah penumpang. Kami akan mengonfirmasi ketersediaan dan mengirimkan harga sebelum Anda memastikan pesanan.' AS answer_id,
         'Pilih kenderaan di bahagian Armada Kami, kemudian tekan "Tempah melalui WhatsApp". Hantar tarikh perjalanan, lokasi ambilan, destinasi dan bilangan penumpang. Kami akan mengesahkan ketersediaan dan menghantar harga sebelum anda mengesahkan tempahan.' AS answer_ms,
         'draft' AS status, NOW() AS created_at, NOW() AS updated_at
  UNION ALL
  SELECT 'Which vehicle is right for my group?',
         'Kendaraan apa yang cocok untuk rombongan saya?',
         'Kenderaan mana yang sesuai untuk kumpulan saya?',
         'Toyota Avanza and Kijang Innova Reborn are 7-seat MPVs for families and small groups. Toyota Alphard is our premium MPV for VIP guests and special occasions. Toyota Hiace carries up to 14 passengers, and our medium bus seats 25 to 35 people for large groups. Tell us how many people and how much luggage you have, and we will suggest the best fit.',
         'Toyota Avanza dan Kijang Innova Reborn adalah MPV 7 kursi untuk keluarga dan grup kecil. Toyota Alphard adalah MPV premium untuk tamu VIP dan acara spesial. Toyota Hiace memuat hingga 14 penumpang, dan bus medium kami berkapasitas 25 sampai 35 kursi untuk rombongan besar. Beri tahu kami jumlah penumpang dan bagasi, kami bantu pilihkan yang paling pas.',
         'Toyota Avanza dan Kijang Innova Reborn ialah MPV 7 tempat duduk untuk keluarga dan kumpulan kecil. Toyota Alphard ialah MPV premium kami untuk tetamu VIP dan acara istimewa. Toyota Hiace memuatkan sehingga 14 penumpang, dan bas sederhana kami mempunyai 25 hingga 35 tempat duduk untuk kumpulan besar. Beritahu kami bilangan penumpang dan bagasi, dan kami akan cadangkan pilihan terbaik.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Does the rental include a driver?',
         'Apakah sewa sudah termasuk sopir?',
         'Adakah sewaan termasuk pemandu?',
         'Yes. Our vehicles come with a friendly driver who knows the roads in Batam, so you can relax and enjoy the trip without worrying about directions or parking.',
         'Ya. Kendaraan kami dikemudikan oleh sopir ramah yang mengenal jalanan Batam, sehingga Anda bisa bersantai menikmati perjalanan tanpa memikirkan rute atau parkir.',
         'Ya. Kenderaan kami dipandu oleh pemandu yang mesra dan mengenali jalan di Batam, jadi anda boleh berehat dan menikmati perjalanan tanpa risau tentang laluan atau tempat letak kereta.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Can you pick me up at the airport or a ferry terminal?',
         'Apakah bisa dijemput di bandara atau pelabuhan feri?',
         'Bolehkah saya diambil di lapangan terbang atau terminal feri?',
         'Yes. We provide transfers from Hang Nadim International Airport and from Batam ferry terminals such as Batam Centre, Harbour Bay, Sekupang and Nongsa Pura. Send us your flight or ferry details and arrival time so the driver can be there when you arrive.',
         'Ya. Kami melayani antar-jemput dari Bandara Internasional Hang Nadim dan pelabuhan feri di Batam seperti Batam Centre, Harbour Bay, Sekupang, dan Nongsa Pura. Kirimkan detail penerbangan atau feri serta jam kedatangan agar sopir sudah siap saat Anda tiba.',
         'Ya. Kami menyediakan pengangkutan dari Lapangan Terbang Antarabangsa Hang Nadim dan terminal feri di Batam seperti Batam Centre, Harbour Bay, Sekupang dan Nongsa Pura. Hantar butiran penerbangan atau feri serta masa ketibaan supaya pemandu sudah bersedia apabila anda tiba.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Can you arrange a city tour or an island trip?',
         'Apakah bisa diatur city tour atau wisata ke pulau?',
         'Bolehkah anda uruskan lawatan bandar atau perjalanan ke pulau?',
         'Yes. Popular stops include Barelang Bridge, Galang and Rempang, Maha Vihara Duta Maitreya and Nongsa. Tell us which places you want to visit and how much time you have, and we will help plan the route. See News & Information for destination ideas.',
         'Bisa. Tujuan favorit antara lain Jembatan Barelang, Galang dan Rempang, Maha Vihara Duta Maitreya, serta Nongsa. Beri tahu kami tempat yang ingin dikunjungi dan waktu yang tersedia, kami bantu atur rutenya. Lihat Berita & Informasi untuk ide destinasi.',
         'Boleh. Antara destinasi popular ialah Jambatan Barelang, Galang dan Rempang, Maha Vihara Duta Maitreya serta Nongsa. Beritahu kami tempat yang ingin dilawati dan masa yang ada, dan kami akan bantu merancang laluan. Lihat Berita & Maklumat untuk idea destinasi.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'How is the price calculated?',
         'Bagaimana harga sewa dihitung?',
         'Bagaimana harga sewaan dikira?',
         'The price depends on the vehicle, the rental duration and your route. We send you a complete quote on WhatsApp, including what it covers, before you confirm, so there are no surprises.',
         'Harga tergantung pada jenis kendaraan, lama sewa, dan rute perjalanan. Kami mengirimkan penawaran lengkap lewat WhatsApp, termasuk apa saja yang sudah tercakup, sebelum Anda memastikan pesanan.',
         'Harga bergantung pada jenis kenderaan, tempoh sewaan dan laluan perjalanan. Kami akan menghantar sebut harga lengkap melalui WhatsApp, termasuk perkara yang diliputi, sebelum anda mengesahkan tempahan.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'How far in advance should I book?',
         'Berapa lama sebelumnya saya sebaiknya memesan?',
         'Berapa awal saya patut membuat tempahan?',
         'Book as early as you can, especially for weekends, public and school holidays, and for the Alphard, Hiace or bus. For last-minute trips, message us anyway and we will check what is available.',
         'Sebaiknya pesan sedini mungkin, terutama untuk akhir pekan, libur nasional dan libur sekolah, serta untuk Alphard, Hiace, atau bus. Untuk perjalanan mendadak, tetap hubungi kami dan kami cek ketersediaannya.',
         'Tempah seawal mungkin, terutamanya untuk hujung minggu, cuti umum dan cuti sekolah, serta untuk Alphard, Hiace atau bas. Untuk perjalanan saat akhir, hubungi kami juga dan kami akan semak ketersediaan.',
         'draft', NOW(), NOW()
  UNION ALL
  SELECT 'Can I change or cancel my booking?',
         'Apakah pesanan bisa diubah atau dibatalkan?',
         'Bolehkah saya menukar atau membatalkan tempahan?',
         'Message us on WhatsApp as soon as your plans change. We will do our best to adjust the date, time or vehicle. The change and cancellation terms for your booking are explained when you book.',
         'Hubungi kami lewat WhatsApp segera setelah rencana Anda berubah. Kami akan berusaha menyesuaikan tanggal, jam, atau kendaraan. Ketentuan perubahan dan pembatalan untuk pesanan Anda dijelaskan saat pemesanan.',
         'Hubungi kami melalui WhatsApp sebaik sahaja rancangan anda berubah. Kami akan cuba sedaya upaya untuk menyesuaikan tarikh, masa atau kenderaan. Syarat perubahan dan pembatalan untuk tempahan anda dijelaskan semasa tempahan dibuat.',
         'draft', NOW(), NOW()
) AS new_faq
WHERE NOT EXISTS (
  SELECT 1 FROM `c_faqs` f WHERE f.`question_en` = new_faq.`question_en` COLLATE utf8mb4_unicode_ci AND f.`status` <> 'deleted'
);