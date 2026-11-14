<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteEvents extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'event_type'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'ref_id'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'ref_label'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'lang'         => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'visitor_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => false],
            'created_at'   => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['event_type', 'created_at'], false, false, 'idx_site_events_type_created');
        $this->forge->addKey('ref_id', false, false, 'idx_site_events_ref');
        $this->forge->createTable('site_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('site_events', true);
    }
}
