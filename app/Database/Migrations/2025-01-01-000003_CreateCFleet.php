<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCFleet extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name_en'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'name_id'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'name_ms'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'description_en' => ['type' => 'TEXT', 'null' => true],
            'description_id' => ['type' => 'TEXT', 'null' => true],
            'description_ms' => ['type' => 'TEXT', 'null' => true],
            'image_path'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'deleted'], 'default' => 'draft', 'null' => false],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('c_fleet', true);
    }

    public function down()
    {
        $this->forge->dropTable('c_fleet', true);
    }
}
