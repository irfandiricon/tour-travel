<?php

namespace App\Models;

class TestimonialModel extends BaseContentModel
{
    protected $table      = 'c_testimonials';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'customer_name', 'photo_path', 'rating', 'review_en', 'review_id', 'review_ms',
        'status', 'created_at', 'updated_at', 'deleted_at',
    ];
}