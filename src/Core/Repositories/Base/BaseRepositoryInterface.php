<?php

declare(strict_types=1);

namespace App\Core\Repositories\Base;
use App\Core\Models\Base\Model;

/**
 * @template TEntity
 */
interface BaseRepositoryInterface
{
    /**
     * Find an entity by ID.
     *
     * @param int $id
     * @return TEntity|null
     */
    public function find(int $id): ?object;

    /**
     * Get all entities.
     *
     * @return TEntity[]
     */
    public function findAll(): array;

    /**
     * Create a new entity.
     *
     * @param array $data
     * @return TEntity|null
     */
    public function create(array $data): ?object;

    /**
     * Update an entity by ID.
     *
     * @param int $id
     * @param array $data
     * @return TEntity|null
     */
    public function update(int $id, array $data): ?object;

    /**
     * Delete an entity by ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Delete multiple entities by IDs.
     *
     * @param array $ids
     * @return int Number of affected rows
     */
    public function deleteMultiple(array $ids): int;

    /**
     * Insert multiple entities.
     *
     * @param array $data
     * @return bool
     */
    public function insertMultiple(array $data): bool;

    /**
     * Find an entity by a specific condition.
     *
     * @param array $conditions
     * @return TEntity|null
     */
    public function findOneBy(array $conditions): ?object;

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): MODEL;
}
