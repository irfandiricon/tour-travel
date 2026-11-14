<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminUserModel extends Model
{
    protected $table            = 'admin_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'username', 'password_hash', 'status',
        'failed_attempts', 'locked_until', 'last_login_at',
        'created_at', 'updated_at',
    ];

    /** Columns that may leave the server (never the password hash). */
    public const PUBLIC_COLUMNS = ['id', 'username', 'status', 'failed_attempts', 'locked_until', 'last_login_at', 'created_at', 'updated_at'];

    /**
     * Find an active admin by username.
     */
    public function findActiveByUsername(string $username): ?array
    {
        return $this->where('username', $username)->where('status', 'active')->first();
    }

    /** True when another account (not $exceptId) already uses this username (case-insensitive collation). */
    public function usernameTaken(string $username, ?int $exceptId = null): bool
    {
        $builder = $this->builder()->where('username', $username);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    public function countActive(): int
    {
        return $this->builder()->where('status', 'active')->countAllResults();
    }

    /** Remove the hash (and anything not public) from a row before it is sent out. */
    public static function toPublic(array $row): array
    {
        return array_intersect_key($row, array_flip(self::PUBLIC_COLUMNS));
    }
}