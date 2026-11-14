<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Entry point: php spark db:seed InitialSeeder
 * Idempotent; safe to run repeatedly.
 */
class InitialSeeder extends Seeder
{
    public function run()
    {
        $this->call('AdminUserSeeder');
        $this->call('SiteConfigSeeder');
    }
}
