<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCFaqs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'question_en' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
            'question_id' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'question_ms' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'answer_en'   => ['type' => 'TEXT', 'null' => false],
            'answer_id'   => ['type' => 'TEXT', 'null' => true],
            'answer_ms'   => ['type' => 'TEXT', 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'deleted'], 'default' => 'draft', 'null' => false],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('c_faqs', true);
    }

    public function down()
    {
        $this->forge->dropTable('c_faqs', true);
    }
}
