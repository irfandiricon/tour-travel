<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the default `site_config` row (idempotent: skipped if any row exists).
 * whatsapp_number is left NULL so the booking action stays hidden until configured.
 */
class SiteConfigSeeder extends Seeder
{
    public function run()
    {
        $builder = $this->db->table('site_config');

        if ($builder->countAllResults() > 0) {
            return; // already present
        }

        $now = date('Y-m-d H:i:s');

        $builder->insert([
            'hero_title_en'    => 'Rent a Car for Your Next Journey',
            'hero_title_id'    => 'Sewa Mobil untuk Perjalanan Anda Berikutnya',
            'hero_title_ms'    => 'Sewa Kereta untuk Perjalanan Anda Seterusnya',

            'hero_subtitle_en' => 'Comfortable, well-maintained vehicles with friendly drivers and flexible rental plans.',
            'hero_subtitle_id' => 'Kendaraan nyaman dan terawat dengan pengemudi ramah dan paket sewa fleksibel.',
            'hero_subtitle_ms' => 'Kenderaan selesa dan terjaga dengan pemandu mesra serta pelan sewaan fleksibel.',

            'about_body_en'    => 'We are a travel and car rental company helping visitors and locals explore with ease. Our fleet is regularly serviced, and our team is ready to plan the right trip for you.',
            'about_body_id'    => 'Kami adalah perusahaan perjalanan dan rental mobil yang membantu wisatawan dan warga lokal menjelajah dengan mudah. Armada kami rutin diservis dan tim kami siap merencanakan perjalanan yang tepat untuk Anda.',
            'about_body_ms'    => 'Kami ialah syarikat pelancongan dan sewaan kereta yang membantu pelancong dan penduduk tempatan meneroka dengan mudah. Armada kami diservis secara berkala dan pasukan kami sedia merancang perjalanan yang sesuai untuk anda.',

            'gallery_title_en' => 'Our Gallery',
            'gallery_title_id' => 'Galeri Kami',
            'gallery_title_ms' => 'Galeri Kami',

            'contact_body_en'  => 'Have a question or ready to book? Get in touch with us and we will reply as soon as possible.',
            'contact_body_id'  => 'Punya pertanyaan atau siap memesan? Hubungi kami dan kami akan membalas secepat mungkin.',
            'contact_body_ms'  => 'Ada soalan atau sedia untuk menempah? Hubungi kami dan kami akan membalas secepat mungkin.',

            'contact_email'    => 'info@example.com',
            'contact_address'  => '123 Example Street, Sample City',
            'whatsapp_number'  => null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }
}
