<?php

namespace App\Models;

use CodeIgniter\Model;

class SiteConfigModel extends Model
{
    protected $table            = 'site_config';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'hero_title_en', 'hero_title_id', 'hero_title_ms',
        'hero_subtitle_en', 'hero_subtitle_id', 'hero_subtitle_ms',
        'about_body_en', 'about_body_id', 'about_body_ms',
        'gallery_title_en', 'gallery_title_id', 'gallery_title_ms',
        'contact_body_en', 'contact_body_id', 'contact_body_ms',
        'contact_email', 'contact_address', 'whatsapp_number',
        'site_name_en', 'site_name_id', 'site_name_ms',
        'meta_description_en', 'meta_description_id', 'meta_description_ms',
        'meta_keywords_en', 'meta_keywords_id', 'meta_keywords_ms',
        'og_image',
        'about_title_en', 'about_title_id', 'about_title_ms', 'about_image',
        'created_at', 'updated_at',
    ];

    /**
     * The site has a single configuration row.
     */
    public function getConfig(): ?array
    {
        return $this->orderBy($this->primaryKey, 'ASC')->first();
    }
}
