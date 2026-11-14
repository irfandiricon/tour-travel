<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCNews extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'title_en'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'title_id'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'title_ms'     => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'body_en'      => ['type' => 'MEDIUMTEXT', 'null' => false],
            'body_id'      => ['type' => 'MEDIUMTEXT', 'null' => true],
            'body_ms'      => ['type' => 'MEDIUMTEXT', 'null' => true],
            'publish_date' => ['type' => 'DATETIME', 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'deleted'], 'default' => 'draft', 'null' => false],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey('publish_date');
        $this->forge->createTable('c_news', true);
    }

    public function down()
    {
        $this->forge->dropTable('c_news', true);
    }
}
