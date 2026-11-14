<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCInformation extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            // TEXT (not VARCHAR(255)) so the 5000-char validator limit can be stored.
            'title_en'    => ['type' => 'TEXT', 'null' => false],
            'title_id'    => ['type' => 'TEXT', 'null' => true],
            'title_ms'    => ['type' => 'TEXT', 'null' => true],
            'body_en'     => ['type' => 'TEXT', 'null' => false],
            'body_id'     => ['type' => 'TEXT', 'null' => true],
            'body_ms'     => ['type' => 'TEXT', 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'deleted'], 'default' => 'draft', 'null' => false],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('c_information', true);
    }

    public function down()
    {
        $this->forge->dropTable('c_information', true);
    }
}
