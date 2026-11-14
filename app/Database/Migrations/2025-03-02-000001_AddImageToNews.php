<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Optional picture for each news item. Idempotent: the column is only added when missing. */
class AddImageToNews extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('image_path', 'c_news')) {
            $this->forge->addColumn('c_news', ['image_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'body_ms']]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('image_path', 'c_news')) {
            $this->forge->dropColumn('c_news', 'image_path');
        }
    }
}