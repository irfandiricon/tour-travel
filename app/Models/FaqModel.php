<?php

namespace App\Models;

class FaqModel extends BaseContentModel
{
    protected $table      = 'c_faqs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'question_en', 'question_id', 'question_ms',
        'answer_en', 'answer_id', 'answer_ms',
        'status', 'created_at', 'updated_at', 'deleted_at',
    ];
}
