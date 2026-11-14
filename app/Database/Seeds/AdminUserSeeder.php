<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds one active admin user into `admin_users` (idempotent).
 *
 * Credentials come from the environment:
 *   ADMIN_SEED_USERNAME  (default: "admin")
 *   ADMIN_SEED_PASSWORD  (REQUIRED for any non-development environment)
 *
 * If ADMIN_SEED_PASSWORD is unset, a well-known DEVELOPMENT-ONLY default is used
 * and the seeder refuses to run in production. Change it immediately after first sign-in.
 */
class AdminUserSeeder extends Seeder
{
    /** Development-only fallback. Never use in a shared or production environment. */
    private const DEV_DEFAULT_PASSWORD = 'admin12345-dev';

    public function run()
    {
        $username = trim((string) env('ADMIN_SEED_USERNAME', 'admin'));
        if ($username === '') {
            $username = 'admin';
        }

        $password = (string) env('ADMIN_SEED_PASSWORD', '');
        if ($password === '') {
            if (ENVIRONMENT === 'production') {
                throw new \RuntimeException('ADMIN_SEED_PASSWORD must be set when seeding in production.');
            }
            $password = self::DEV_DEFAULT_PASSWORD;
        }

        $builder = $this->db->table('admin_users');

        if ($builder->where('username', $username)->countAllResults() > 0) {
            return; // already present
        }

        $now = date('Y-m-d H:i:s');
        $builder->insert([
            'username'        => $username,
            'password_hash'   => password_hash($password, PASSWORD_DEFAULT),
            'status'          => 'active',
            'failed_attempts' => 0,
            'locked_until'    => null,
            'last_login_at'   => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }
}
