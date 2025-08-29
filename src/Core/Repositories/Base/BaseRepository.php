<?php

declare(strict_types=1);

namespace App\Core\Repositories\Base;

use App\Core\Models\Base\Model;
use PDO;

/**
 * @template TEntity
 * @implements BaseRepositoryInterface<TEntity>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected PDO $db;
    protected string $table;
    protected string $entityClass;
    protected Model $model;
    protected array $params = [];

    /**
     * @param PDO $db
     * @param string $table
     * @param string $entityClass The fully qualified class name of the entity.
     */
    public function __construct(PDO $db, string $table, string $entityClass)
    {
        $this->db = $db;
        $this->table = $table;
        $this->entityClass = $entityClass;
        $this->model = new $entityClass();
        $this->model->setDb($this->db);
    }

    protected function getPrimaryKeyColumn(): string
    {
        return "{$this->table}_id";
    }

    /**
     * Get the total count for a query
     */
    protected function getCount(string $sql): int
    {
        $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as count_query";
        $stmt = $this->db->prepare($countSql);
        
        // Clone the parameters to avoid modifying the original query's parameters
        $params = $this->params;
        
        // Execute with the cloned parameters
        $stmt->execute($params);
        
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Find an entity by ID.
     *
     * @param int $id
     * @return TEntity|null
     */
    public function find(int $id): ?object
    {
        return $this->model->find($id);
    }

    /**
     * Get all entities.
     *
     * @return TEntity[]
     */
    public function findAll(): array
    {
        return $this->model->findAll() ?? [];
    }

    /**
     * Create a new entity.
     *
     * @param array $data
     * @return TEntity|null
     */
    public function create(array $data): ?object
    {
        return $this->model->create($data);
    }

    /**
     * Update an entity by ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): ?Model
    {
        $entity = $this->model->find($id);
        if (!$entity) {
            return null;
        }
        return $entity->update($data);
    }

    /**
     * Delete an entity by ID.
     *
     * @param int $id
     * @return bool
     * @throws \PDOException When database error occurs
     */
    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }

    /**
     * Delete multiple entities by IDs.
     *
     * @param array $ids
     * @return int Number of affected rows
     * @throws \PDOException When database error occurs
     */
    public function deleteMultiple(array $ids): int
    {
        return $this->model->deleteMultiple($ids);
    }

    /**
     * Insert multiple entities.
     *
     * @param array $data
     * @return bool Number of affected rows
     * @throws \PDOException When database error occurs
     */
    public function insertMultiple(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * Find an entity by a specific condition.
     *
     * @param array $conditions
     * @return TEntity|null
     */
    public function findOneBy(array $conditions): ?object
    {
        return $this->model->findOneBy($conditions);
    }

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
