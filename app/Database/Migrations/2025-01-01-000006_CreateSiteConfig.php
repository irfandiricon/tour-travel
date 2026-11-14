<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteConfig extends Migration
{
    public function up()
    {
        $fields = [
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
        ];

        // Translatable section columns: [base => [type, constraint|null]]
        $translatable = [
            'hero_title'    => ['VARCHAR', 255],
            'hero_subtitle' => ['VARCHAR', 500],
            'about_body'    => ['TEXT', null],
            'gallery_title' => ['VARCHAR', 255],
            'contact_body'  => ['TEXT', null],
        ];

        foreach ($translatable as $base => [$type, $constraint]) {
            foreach (['en', 'id', 'ms'] as $lang) {
                $def = ['type' => $type, 'null' => true];
                if ($constraint !== null) {
                    $def['constraint'] = $constraint;
                }
                $fields["{$base}_{$lang}"] = $def;
            }
        }

        $fields['contact_email']    = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true];
        $fields['contact_address']  = ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true];
        $fields['whatsapp_number']  = ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true];
        $fields['created_at']       = ['type' => 'DATETIME', 'null' => true];
        $fields['updated_at']       = ['type' => 'DATETIME', 'null' => true];

        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('site_config', true);
    }

    public function down()
    {
        $this->forge->dropTable('site_config', true);
    }
}
