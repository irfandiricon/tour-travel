<?php

namespace App\Models;

use CodeIgniter\Model;

class SiteEventModel extends Model
{
    protected $table            = 'site_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'event_type', 'ref_id', 'ref_label', 'lang', 'visitor_hash', 'created_at',
    ];
}
