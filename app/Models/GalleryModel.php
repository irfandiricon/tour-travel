<?php

namespace App\Models;

class GalleryModel extends BaseContentModel
{
    protected $table      = 'c_gallery';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'caption_en', 'caption_id', 'caption_ms',
        'image_path', 'sort_order',
        'status', 'created_at', 'updated_at', 'deleted_at',
    ];
}
