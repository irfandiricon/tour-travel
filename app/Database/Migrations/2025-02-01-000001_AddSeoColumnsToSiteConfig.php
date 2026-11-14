<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSeoColumnsToSiteConfig extends Migration
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function columns(): array
    {
        $cols = [];
        foreach (['en', 'id', 'ms'] as $lang) {
            $cols["site_name_{$lang}"]        = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true];
            $cols["meta_description_{$lang}"] = ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true];
            $cols["meta_keywords_{$lang}"]    = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true];
        }
        $cols['og_image'] = ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true];

        return $cols;
    }

    public function up()
    {
        foreach ($this->columns() as $name => $def) {
            if (! $this->db->fieldExists($name, 'site_config')) {
                $this->forge->addColumn('site_config', [$name => $def]);
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->columns()) as $name) {
            if ($this->db->fieldExists($name, 'site_config')) {
                $this->forge->dropColumn('site_config', $name);
            }
        }
    }
}
