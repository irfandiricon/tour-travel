<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVisitorLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'visit_date'    => ['type' => 'DATE', 'null' => false],
            'visitor_hash'  => ['type' => 'CHAR', 'constraint' => 64, 'null' => false],
            'page'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'lang'          => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'device_type'   => ['type' => 'ENUM', 'constraint' => ['desktop', 'mobile', 'tablet', 'bot'], 'null' => false],
            'referrer_host' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_bot'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
            'created_at'    => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('visit_date', false, false, 'idx_visitor_logs_visit_date');
        $this->forge->addKey('visitor_hash', false, false, 'idx_visitor_logs_visitor_hash');
        $this->forge->createTable('visitor_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('visitor_logs', true);
    }
}
