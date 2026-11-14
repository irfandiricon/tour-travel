<?php

namespace App\Models;

use CodeIgniter\Model;

class VisitorLogModel extends Model
{
    protected $table            = 'visitor_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'visit_date', 'visitor_hash', 'page', 'lang', 'device_type',
        'referrer_host', 'is_bot', 'created_at',
    ];
}
