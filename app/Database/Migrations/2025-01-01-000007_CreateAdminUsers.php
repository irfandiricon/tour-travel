<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'username'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'password_hash'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active', 'null' => false],
            'failed_attempts' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
            'locked_until'    => ['type' => 'DATETIME', 'null' => true],
            'last_login_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('admin_users', true);
    }

    public function down()
    {
        $this->forge->dropTable('admin_users', true);
    }
}
