<?php

namespace App\Models;

class NewsModel extends BaseContentModel
{
    protected $table      = 'c_news';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'title_en', 'title_id', 'title_ms',
        'body_en', 'body_id', 'body_ms',
        'image_path',
        'publish_date',
        'status', 'created_at', 'updated_at', 'deleted_at',
    ];
}
