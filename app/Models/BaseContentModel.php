<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Shared behaviour for content entities that use a `status` column
 * (draft / published / deleted) for soft deletion.
 */
abstract class BaseContentModel extends Model
{
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false; // soft delete is status-based, see softDelete()
    protected $useTimestamps  = true;
    protected $dateFormat     = 'datetime';
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    /**
     * Rows that are not soft-deleted (draft + published).
     */
    public function notDeleted(): static
    {
        return $this->whereNotIn('status', ['deleted']);
    }

    /**
     * Publicly visible rows only.
     */
    public function published(): static
    {
        return $this->where('status', 'published');
    }

    /**
     * Find a single non-deleted row by id.
     */
    public function findActive(int $id): ?array
    {
        return $this->whereNotIn('status', ['deleted'])->where($this->primaryKey, $id)->first();
    }

    /**
     * Soft delete: mark status as deleted and stamp deleted_at.
     */
    public function softDelete(int $id): bool
    {
        return (bool) $this->update($id, [
            'status'     => 'deleted',
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
