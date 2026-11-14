<?php

namespace App\Models;

class FleetModel extends BaseContentModel
{
    protected $table      = 'c_fleet';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'name_en', 'name_id', 'name_ms',
        'description_en', 'description_id', 'description_ms',
        'image_path',
        'status', 'created_at', 'updated_at', 'deleted_at',
    ];
}
