<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** "Price starting from" (whole rupiah) for each fleet item; NULL hides the price. Idempotent. */
class AddPriceToFleet extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('price_from', 'c_fleet')) {
            $this->forge->addColumn('c_fleet', ['price_from' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'description_ms']]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('price_from', 'c_fleet')) {
            $this->forge->dropColumn('c_fleet', 'price_from');
        }
    }
}