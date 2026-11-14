<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCGallery extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'caption_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'caption_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'caption_ms' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'image_path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
            'status'     => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'deleted'], 'default' => 'draft', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey('sort_order');
        $this->forge->createTable('c_gallery', true);
    }

    public function down()
    {
        $this->forge->dropTable('c_gallery', true);
    }
}
