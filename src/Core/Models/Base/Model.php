<?php

namespace App\Core\Models\Base;

use PDO;

/**
 * @template T
 */
abstract class Model {
    /**
     * Data
     */
    public object $data;
    /**
     * Table name
     */
    protected string $table;
    /**
     * Table Alias
     */
    protected string $tableAlias;

    /**
     * Related Table Alias
     */
    protected ?string $relatedTableAlias = null;
    /** 
     * SQL Query
     */
    protected string $query = '';
    /**
     * Select
     */
    protected string $select = '*';
    /**
     * Columns
     */
    protected array $columns = [];
    /**
     * Cache for relationships
     */
    protected array $relationCache = [];
    /**
     * Eager loading relationships
     */
    protected array $withRelations = [];
    /**     
     * Where conditions
     */
    protected array $whereConditions = [];    
    /**
     * Parameters
     */
    protected array $params = [];
    /**
     * Joins
     */
    protected array $joins = [];
    /**
     * Group by
     */
    protected array $groupBy = [];
    /**
     * Order by
     */
    protected array $orderBy = [];
    /**
     * Limit
     */
    protected int $limitValue = 100;
    /**
     * Offset
     */
    protected int $offsetValue = 0;
    /**
     * PDO instance
     */
    protected ?\PDO $db = null;

    /**
     * Magic method to dynamically set properties that don't exist in the class
     * 
     * @param string $name Property name
     * @param mixed $value Property value
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->data->$name = $value;
    }

    /**
     * Magic method to dynamically get properties that don't exist in the class
     * 
     * @param string $name Property name
     * @return mixed Property value or null if not found
     */
    public function __get(string $name)
    {
        if(isset($this->$name)){
            return $this->$name;
        }else{
            return $this->data->$name;
        }
    }


    /**
     * Magic method to check if a property exists
     * 
     * @param string $name Property name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->$name);
    }

    public function __construct()
    {
        $this->data = new \stdClass();
    }
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }
    public function clearQuery(): self
    {
        $this->query = '';
        $this->columns = [];
        $this->whereConditions = [];
        $this->params = [];
        $this->joins = [];
        $this->groupBy = [];
        $this->orderBy = [];
        $this->limitValue = 100;
        $this->offsetValue = 0;
        $this->select = '*';
        return $this;
    }

    /**
     * Set PDO instance
     */
    public function setDb(\PDO $db): void
    {
        $this->db = $db;
    }

    public function getId()
    {
        $key = $this->getPrimaryKey();
        return $this->$key;
    }

    /**
     * Get PDO instance
     */
    protected function getDb(): \PDO
    {
        if ($this->db === null) {
            throw new \RuntimeException('Database connection not set. Call setDb() before using the model.');
        }
        return $this->db;
    }

    public function getParams(): array
    {
        return $this->params;
    }
    /**
     * Dynamically set properties from the provided data array.
     *
     * @param array|null $data
     * @return void
     */
    public function set(?array $data): ?self
    {
        if (!is_array($data) || empty($data)) {
            return null;
        }

        foreach ($data as $key => $value) {
            $methodSnake = str_replace('_data', '', $key);
            $method = lcfirst(str_replace('_', '', ucwords($methodSnake, '_'))); // Convert snake_case to camelCase
            if (property_exists($this, $key)) {
                if(!isset($this->data)) $this->data = new \stdClass();
                $this->$key = $value;
                $this->__set($key, $value);
            }else if(method_exists($this, $method)){
                if(!isset($this->data)) $this->data = new \stdClass();
                $this->$method = $value;
                $this->__set($method, $value);
            } else if (method_exists($this, $methodSnake)){
                if(!isset($this->data)) $this->data = new \stdClass();
                $this->$methodSnake = $value;
                $this->__set($methodSnake, $value);
            }
            else if(str_contains($key, '_data')){
                $method = str_replace('_data', '', $key);
                $method = lcfirst(str_replace('_', '', ucwords($method, '_')));
                $data = json_decode($value, true);
                if(method_exists($this, $method)){
                    $this->$method = $data;
                    $this->__set($method, $data);
                }else if(method_exists($this, $method.'s')){
                    $this->{$method.'s'} = $data;
                    $this->__set($method.'s', $data);
                }

            }
        }
        return $this;
    }

    /**
     * Get the primary key for the model
     */
    public function getPrimaryKey(): string
    {
        $table = $this->getTable();
        return str_replace('`', '', $table) . '_id';
    }

    /**
     * Get the table name for the model
     */
    public function getTable(): string
    {
        if (empty($this->table)) {
            $class = basename(str_replace('\\', '/', static::class));
            $this->table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
        }
        return "`{$this->table}`";
    }

    public function setTableAlias(string $tableAlias): self
    {
        if(!str_contains($tableAlias, '`')){
            $tableAlias = "`{$tableAlias}`";
        }
        $this->tableAlias = $tableAlias;
        return $this;
    }

    public function getTableAlias(): string
    {
        $tableAlias = $this->tableAlias ?? $this->getTable();
        if(!str_contains($tableAlias, '`')){
            $tableAlias = "`{$tableAlias}`";
        }
        return $tableAlias;
    }

    public function prepareKeys($relatedModel, $foreignKey, $localKey, $relatedTableAlias = null){
        $relatedObject = new $relatedModel();
        $relatedObject->setDb($this->db);
        $tableAlias = $this->getTableAlias();
        $relatedTable = $relatedObject->getTable();
        $relatedTableAlias = $relatedTableAlias ?? $relatedObject->getTableAlias();
        $relatedObject->setTableAlias($relatedTableAlias);
        $foreignKey = $foreignKey ?? $relatedObject->getPrimaryKey();
        $localKey = $localKey ?? $this->getPrimaryKey();
        return [$tableAlias, $relatedObject, $relatedTable, $relatedTableAlias, $foreignKey, $localKey];
    }

    public function prepareBelongKeys($ownerModel, $foreignKey, $ownerKey, $ownerTableAlias = null){
        $ownerObject = new $ownerModel();
        $ownerObject->setDb($this->db);
        $tableAlias = $this->getTable();
        $ownerTable = $ownerObject->getTable();
        $ownerTableAlias = $ownerTableAlias ?? $ownerObject->getTableAlias();
        $ownerObject->setTableAlias($ownerTableAlias);
        $ownerKey = $ownerKey ?? $ownerObject->getPrimaryKey();
        $foreignKey = $foreignKey ?? $ownerKey;
        return [$tableAlias, $ownerObject, $ownerTable, $ownerTableAlias, $foreignKey, $ownerKey];
    }

    /**
     * Get default pivot table name for many-to-many relationship
     */
    public function getDefaultPivotTableName(string $relatedTable): string
    {
        $tables = [$this->getTable(), $relatedTable];
        sort($tables);
        return implode('_', $tables);
    }

    /**
     * Get class name without namespace
     */
    public function class_basename($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Get all columns from a table
     * 
     * @param string $table
     * @return array
     */
    public function getTableColumns(string $table, $alias = null, $model = null): array
    {
        $model = $model ?? $this;
        $sql = "SHOW COLUMNS FROM {$table}";
        $stmt = $this->getDb()->query($sql);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Field');
        // $tableAlias = $alias ?? $model->getTableAlias();
        // $cols = [];
        // foreach($columns as $column){
        //     $cols[$column] = "{$tableAlias}.{$column}";
        // }
        // return $cols;
    }
    public function getJsonObject($table, $alias, $model = null, $columns = []){
        if(empty($columns)){
            $columns = $this->getTableColumns($table, $alias, $model);
        }
        $json = "JSON_OBJECT(" . implode(", ", array_map(function($column) use ($alias) {
            if(str_contains($column, 'JSON_OBJECT')){
                $colname = explode("AS", $column);
                $colname = trim($colname[1]);
                return "'{$colname}', {$column}";
            }
            else{
                $col = str_replace($alias.'.', '', $column);
                return "'{$col}', {$column}";
            }
        }, $columns)) . ")";

       
        return [$json, $columns];
    }
    /**
     * Get all columns from a table
     * 
     * @param string $table
     * @return array
     */
    public function prepareColumns(array $columns, $table, $alias = null, $model = null): array
    {
        $model = $model ?? $this;
        if(empty($columns)){
            $columns = $this->getTableColumns($table, $alias, $model);
        }
        $tableAlias = $alias ?? $model->getTableAlias();
        $cols = [];
        foreach($columns as $column){
            if(!str_contains($column, '.')){
                $cols[$column] = "{$tableAlias}.{$column}";
            }else if(str_contains($column, ' as ')){
                $column_parts = explode(" as ", $column);
                $cols[$column_parts[1]] = $column;
            }
            else{
                $parts = explode('.', $column);
                $cols[$parts[1]] = $column;
            }
        }
        return $cols;
    }
    private function processColumn(array $columns, string $tableAlias): string 
    {
        foreach($columns as $key => $column){
            if(!str_contains($column, '.')){
                $columns[$key] = "{$tableAlias}.{$column}";
            }
            else if(str_contains($column, ' as ')){
                $column_parts = explode(" as ", $column);
                if(str_contains($column_parts[0], '.')){
                    $columns[$key] = $column;
                }else{
                    $columns[$key] = "{$tableAlias}.{$column_parts[0]} as {$column_parts[1]}";
                }
            }
            else if(str_contains($column, 'JSON_OBJECT')){
                $columns[$key] = "{$column} as {$key}";
            }
            else if(str_contains($column, '(') && str_contains($column, ')')){
                $columns[$key] = "{$column} as {$key}";
            }
            else{
                $columns[$key] = $column;
            }
        }
        return implode(", ", $columns);
    }
    private function processColumnRecursively(array|string $column, string|null $tableAlias = null, string|null $field = null): string {
        if (is_string($column)) {
            if(str_contains($column, ' as ')){
                $column_parts = explode(" as ", $column);
                return "'{$column_parts[1]}', {$column_parts[0]}";
            }else{
                return "'{$field}', {$column}";
            }
        } elseif (is_array($column) && isset($column['columns']) && isset($column['type'])) {
            if ($column['type'] === 'one') {
                $string = "JSON_OBJECT(" . implode(", ", array_map(function($col, $key)use($tableAlias){
                    return $this->processColumnRecursively($col, $tableAlias, $key);
                }, $column['columns'], array_keys($column['columns']))) . ")";
            } else if($column['type'] === 'many'){
                $string = "JSON_ARRAYAGG(JSON_OBJECT(" . implode(", ", array_map(function($col, $key)use($tableAlias){
                    return $this->processColumnRecursively($col, $tableAlias, $key);
                }, $column['columns'], array_keys($column['columns']))) . "))";
            }
            else if($column['type'] === 'none'){
                $string = implode(", ", array_map(function($col, $key){
                    if(is_string($col)){
                        return "'{$key}', {$col}";
                    }else if(is_array($col) && isset($col['columns'])){
                        $col['field'] = $key;
                        return $this->processColumnRecursively($col);
                    }
                }, $column['columns'], array_keys($column['columns'])));
            }else{
                
            }
            if(($column['type'] === 'none' && $tableAlias) ||
                (!$field && $tableAlias)
            ){
                return "{$string} as {$tableAlias}";
            }else{
                return "'{$field}', {$string}";
            }
        }
        return '';
    }

    public function prepareSelectStatement(){

    }

    /**
     * Eager load relationships
     * 
     * @param array $relations
     * @return static
     */
    public function with(array $relations): static
    {
        foreach ($relations as $key => $value) {
            $this->relatedTableAlias = null;
            $relationAlias = null;
            $relation = "";
            $callback = null;
            if (is_numeric($key) && is_string($value)) {
                $relation = $value;
                $relationAlias = $relation;
            } elseif (is_string($key) && is_callable($value)) {
                $relation = $key;
                $relationAlias = $key;
                if(str_contains($relation, ' as ')){
                    $relationParts = explode(" as ", $relation);
                    $relation = $relationParts[0];
                    $relationAlias = $relationParts[1];
                }
                if(!str_contains($relationAlias, '`')){
                    $relationAlias = "`{$relationAlias}`";
                }
                $this->relatedTableAlias = $relationAlias;
                $callback = $value;
            }
            //Check relation is defined as method in this model
            if (method_exists($this, $relation)) {
                $this->withRelations[] = $relationAlias;
                $relationCache = $this->$relation();
                $this->relationCache[$relationAlias] = $relationCache;
                $this->joins = array_merge($this->joins, [$relationAlias => $relationCache['joins']]);
                $this->columns = array_merge($this->columns, 
                    [$relationAlias => 
                        [
                            'columns' => $relationCache['columns'], 
                            'type' => $relationCache['type'],
                            'tableAlias' => $relationCache['tableAlias']
                        ]
                    ]);
                
              
                if($callback){
                    $relatedModel = $this->relationCache[$relationAlias]['model'];
                    $relatedModel = $callback($relatedModel);
                      //From relationship 
                    //1 Update select columns in $this->columns 
                    if(!empty($relatedModel->columns)){
                        $cols = $this->columns;
                        foreach($relatedModel->columns as $key => $col){
                            if(isset($cols[$key])){
                                // Get only new columns that aren't in relationCache
                                $newColumns = array_diff_key(
                                    $this->columns[$relationAlias]['columns'], 
                                    $this->relationCache[$relationAlias]['columns']
                                );
                                // Merge new columns with the current columns
                                $this->columns[$relationAlias]['columns'] = array_merge($newColumns, $col['columns']);
                            }else{
                                $this->columns[$relationAlias]['columns'][$key] = $col;
                            }
                        }
                    }
                    //2 Update joins in $this->joins
                    if(!empty($relatedModel->joins)){
                        $this->joins[$relationAlias] = array_merge($this->joins[$relationAlias]??[], $relatedModel->joins);
                    }
                    //3 Update where conditions in $this->whereConditions
                    if(!empty($relatedModel->whereConditions)){
                        $this->whereConditions = array_merge($this->whereConditions??[], $relatedModel->whereConditions);
                    }
                    //4 Update order by in $this->orderBy
                    if(!empty($relatedModel->orderBy)){
                        $this->orderBy = array_merge($this->orderBy??[], $relatedModel->orderBy);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Define a one-to-one relationship
     * 
     * @template TRelated
     * @param class-string<TRelated> $relatedClass Related model class
     * @param string|null $foreignKey Foreign key on related table
     * @param string|null $localKey Local key on this table
     * @return array{join: string, select: string}
     */
    public function hasOne($relatedModel, $foreignKey = null, $localKey = null)
    {
        $relatedTableAlias = $this->relatedTableAlias;
        [$tableAlias, $relatedObject, $relatedTable, $relatedTableAlias, $foreignKey, $localKey] = $this->prepareKeys($relatedModel, $foreignKey, $localKey, $relatedTableAlias);
        
        $columns = $this->prepareColumns([], $relatedTable, $relatedTableAlias, $relatedObject);
        
        return
        [
            'join' => "LEFT JOIN {$relatedTable} AS {$relatedTableAlias} ON {$tableAlias}.{$foreignKey} = {$relatedTableAlias}.{$localKey}", 
            'class' => $relatedModel,
            'model' => $relatedObject,
            'tableAlias' => $relatedTableAlias,
            "type" => "one",
            "columns" => $columns,
            'joins' => [[
                'table' => "{$relatedTable} AS {$relatedTableAlias}",
                'first' => "{$tableAlias}.{$foreignKey}",
                'operator' => '=',
                'second' => "{$relatedTableAlias}.{$localKey}",
                'type' => 'LEFT',
                'aliased' => true
            ]]
        ];
    }

    /**
     * Define a one-to-many relationship
     * 
     * @template TRelated
     * @param class-string<TRelated> $relatedClass Related model class
     * @param string|null $foreignKey Foreign key on related table
     * @param string|null $localKey Local key on this table
     * @return array{join: string, select: string}
     */
    public function hasMany($relatedModel, $foreignKey = null, $localKey = null)
    {
        $relatedTableAlias = $this->relatedTableAlias;
        [$tableAlias, $relatedObject, $relatedTable, $relatedTableAlias, $foreignKey, $localKey] = $this->prepareKeys($relatedModel, $foreignKey, $localKey, $relatedTableAlias);
        
        $columns = $this->prepareColumns([], $relatedTable, $relatedTableAlias, $relatedObject);
        
        return
        [
            'join' => "LEFT JOIN {$relatedTable} AS {$relatedTableAlias} ON {$tableAlias}.{$foreignKey} = {$relatedTableAlias}.{$localKey}",
            'model' => $relatedObject,
            'tableAlias' => $relatedTableAlias,
            'class' => $relatedModel,
            "type" => "many",
            'columns' => $columns,
            'joins' => [[
                'table' => "{$relatedTable} AS {$relatedTableAlias}",
                'first' => "{$tableAlias}.{$foreignKey}",
                'operator' => '=',
                'second' => "{$relatedTableAlias}.{$localKey}",
                'type' => 'LEFT',
                'aliased' => true
            ]]
        ];
    }

    /**
     * Define a many-to-one relationship
     * 
     * @template TRelated
     * @param class-string<TRelated> $relatedClass Related model class
     * @param string|null $foreignKey Foreign key on this table
     * @param string|null $ownerKey Primary key on related table
     * @return array{join: string, select: string}
     */
    public function belongsTo(
        $relatedModel, 
        $foreignKey = null, 
        $ownerKey = null)
    {
        $relatedTableAlias = $this->relatedTableAlias;
        [$tableAlias, $ownerObject, $ownerTable, $ownerTableAlias, $foreignKey, $ownerKey] = $this->prepareBelongKeys($relatedModel, $foreignKey, $ownerKey, $relatedTableAlias);
        

        $columns = $this->prepareColumns([], $ownerTable, $ownerTableAlias, $ownerObject);

        return [
            'join' => "LEFT JOIN {$ownerTable} AS {$ownerTableAlias} ON {$tableAlias}.{$foreignKey} = {$ownerTableAlias}.{$ownerKey}", 
            'model' => $ownerObject,
            'tableAlias' => $ownerTableAlias,
            'class' => $relatedModel,
            "type" => "one",
            'columns' => $columns,
            'joins' => [[
                'table' => "{$ownerTable} AS {$ownerTableAlias}",
                'first' => "{$tableAlias}.{$foreignKey}",
                'operator' => '=',
                'second' => "{$ownerTableAlias}.{$ownerKey}",
                'type' => 'LEFT',
                'aliased' => true
            ]]
        ];
    }

    /**
     * Define a many-to-many relationship
     * 
     * @template TRelated
     * @param class-string<TRelated> $relatedClass Related model class
     * @param string $pivotTable Pivot table name
     * @param string|null $foreignPivotKey Foreign key on pivot table for this model
     * @param string|null $relatedPivotKey Foreign key on pivot table for related model
     * @param string|null $localKey Local key on this table
     * @param string|null $relatedKey Local key on related table
     * @return array{join: string, select: string}
     */
    public function belongsToMany(
        $relatedModel, 
        $pivotTable = null, 
        $pivotTableAlias = null,
        $foreignPivotKey = null, 
        $ownerPivotKey = null, 
        $localKey = null, 
        $ownerKey = null
    )
    {
        $relatedTableAlias = $this->relatedTableAlias;
        $tableAlias = $this->getTableAlias();
        $relatedObject = new $relatedModel();
        $relatedObject->setDb($this->db);
        $relatedObject->setTableAlias($relatedTableAlias);
        $relatedTable = $relatedObject->getTable();
        $relatedTableAlias = $relatedTableAlias ?? $relatedObject->getTableAlias();
        $foreignPivotKey = $foreignPivotKey ?? $this->getPrimaryKey();
        $ownerPivotKey = $ownerPivotKey ?? $relatedObject->getPrimaryKey();
        $localKey = $localKey ?? $this->getPrimaryKey();
        $ownerKey = $ownerKey ?? $relatedObject->getPrimaryKey();

        // Get pivot table name before using it
        $pivotTable = $pivotTable ?? $this->getDefaultPivotTableName($relatedTable);
        $pivotAlias = $pivotTableAlias ?? $pivotTable."_default"; // Use first two letters of pivot table name
        
        // $this->withRelations[] = "{$relatedTable}_data";

        $columns = $this->prepareColumns([], $relatedTable, $relatedTableAlias, $relatedObject);

        return [
            'join' => "LEFT JOIN {$pivotTable} AS {$pivotAlias} ON {$pivotAlias}.{$foreignPivotKey} = {$tableAlias}.{$localKey} 
                      LEFT JOIN {$relatedTable} AS {$relatedTableAlias} ON {$relatedTableAlias}.{$ownerKey} = {$pivotAlias}.{$ownerPivotKey}",
            'model' => $relatedObject,
            'tableAlias' => $relatedTableAlias,
            'class' => $relatedModel,
            "type" => "many",
            'columns' => $columns,
            'joins' => [
                [
                    'table' => "{$pivotTable} AS {$pivotAlias}",
                    'first' => "{$pivotAlias}.{$foreignPivotKey}",
                    'operator' => '=',
                    'second' => "{$tableAlias}.{$localKey}",
                    'type' => 'LEFT',
                    'aliased' => true
                ],
                [
                    'table' => "{$relatedTable} AS {$relatedTableAlias}",
                    'first' => "{$relatedTableAlias}.{$ownerKey}",
                    'operator' => '=',
                    'second' => "{$pivotAlias}.{$ownerPivotKey}",
                    'type' => 'LEFT',
                    'aliased' => true
            ]]
        ];
    }

    public function create(array $data): ?Model
    {
        $this->query = "";
        // $columns = implode(', ', array_keys($data));
        $columns = implode(', ', array_map(function($col) {
            return "`$col`";
        }, array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));
        // $sql = "INSERT INTO `{$this->getTable()}` ({$columns}) VALUES ({$placeholders})";
        $tableName = str_replace('`', '', $this->getTable());
        $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $this->query = $sql;
        $stmt->execute();
        $id = (int)$this->db->lastInsertId();
        return $this->find($id);
    }
    public function createWithCompositeKey(array $keys, array $data): ?Model
    {
        $this->query = "";
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO `{$this->getTable()}` ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $this->query = $sql;
        $stmt->execute();
        $id = (int)$this->db->lastInsertId();
        foreach($keys as $key){
            $this->where($key, '=', $data[$key]);
        }
        return $this->first();
    }

    public function update(array $data): ?Model
    {
        $this->query = "";
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "`$key` = :$key";
        }
        $setClause = implode(', ', $sets);
        $primaryKey = $this->getPrimaryKey();

        $sql = "UPDATE `{$this->table}` SET {$setClause} WHERE `{$primaryKey}` = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $this->getId(), PDO::PARAM_INT);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $this->query = $sql;
        $stmt->execute();
        return $this->find($this->getId());
    }

    public function delete(int $id): bool
    {
        $this->query = "";
        $primaryKey = $this->getPrimaryKey();
        $sql = "DELETE FROM `{$this->table}` WHERE {$primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->query = $sql;
        return $stmt->execute();
    }
    
    /**
     * Insert or update multiple records (upsert)
     * 
     * @param array $data Array of records to insert/update
     * @param array $uniqueKeys The unique keys to check for existing records
     * @return int|false The number of affected rows or false on failure
     * @throws \PDOException When database error occurs
     */
    public function upsert(array $data, array $uniqueKeys): int|false
    {
        $this->query = "";
        try {
            if (empty($data)) {
                return 0;
            }
            

            // If there is no zero index and available from 1.
            if(!isset($data[0]) && isset($data[1])){
                $columns = array_keys($data[1]);
            }
            else {
                // Get columns from first record
                $columns = array_keys($data[0]);
            }


            
            // Build the ON DUPLICATE KEY UPDATE part
            $updateParts = [];
            foreach ($columns as $key) {
                // Skip unique key columns in update clause
                // if (in_array($key, $uniqueKeys)) {
                //     continue;
                // }
                // For non-numeric values, replace the value
                $updateParts[] = "`$key` = VALUES(`$key`)";
                
            }

            // Build the VALUES part for multiple records
            $valueSets = [];
            $params = [];
            $paramIndex = 0;
            
            foreach ($data as $record) {
                $placeholders = [];
                foreach ($columns as $column) {
                    $paramName = ":p{$paramIndex}";
                    $placeholders[] = $paramName;
                    $params[$paramName] = $record[$column];
                    $paramIndex++;
                }
                $valueSets[] = "(" . implode(", ", $placeholders) . ")";
            }
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s",
                $this->getTable(),
                implode(', ', array_map(fn($col) => "`$col`", $columns)),
                implode(', ', $valueSets),
                implode(', ', $updateParts)
            );
            
            $this->query = $sql;
            $stmt = $this->getDb()->prepare($sql);
            
            // Bind all parameters
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            if ($stmt->execute()) {
                return $stmt->rowCount();
            }
            
            return false;
        } catch (\PDOException $e) {
            throw new \PDOException("Upsert operation failed: " . $e->getMessage());
        }
    }
    /**
     * Insert multiple records at once
     * 
     * @param array $records Array of records to insert
     * @return bool Whether the insertion was successful
     * @throws \PDOException When database error occurs
     */
    public function insert(array $records): bool
    {
        $this->query = "";
        try {
            if (empty($records)) {
                return true;
            }

            // Get columns from first record
            $columns = array_keys($records[0]);
            
            // Build the VALUES part for multiple records
            $valueSets = [];
            $params = [];
            $paramIndex = 0;
            
            foreach ($records as $record) {
                $placeholders = [];
                foreach ($columns as $column) {
                    $paramName = ":p{$paramIndex}";
                    $placeholders[] = $paramName;
                    $params[$paramName] = $record[$column];
                    $paramIndex++;
                }
                $valueSets[] = "(" . implode(", ", $placeholders) . ")";
            }
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES %s",
                $this->getTable(),
                implode(', ', array_map(fn($col) => "`$col`", $columns)),
                implode(', ', $valueSets)
            );
            
            $stmt = $this->getDb()->prepare($sql);
            $this->query = $sql;
            
            // Bind all parameters
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            throw new \PDOException("Insert operation failed: " . $e->getMessage());
        }
    }

    /**
     * Delete multiple records by IDs
     * 
     * @param array $ids Array of IDs to delete
     * @return int Number of affected rows
     * @throws \PDOException When database error occurs
     */
    public function deleteMultiple(array $ids): int
    {
        $this->query = "";
        try {
            if (empty($ids)) {
                return 0;
            }

            $primaryKey = $this->getPrimaryKey();
            $placeholders = [];
            $params = [];
            
            // Create placeholders and parameters for each ID
            foreach ($ids as $index => $id) {
                $paramName = ":id{$index}";
                $placeholders[] = $paramName;
                $params[$paramName] = $id;
            }
            
            $sql = sprintf(
                "DELETE FROM %s WHERE %s IN (%s)",
                $this->getTable(),
                $primaryKey,
                implode(', ', $placeholders)
            );
            
            $stmt = $this->getDb()->prepare($sql);
            $this->query = $sql;
            
            // Bind all parameters
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \PDOException("Delete multiple operation failed: " . $e->getMessage());
        }
    }

     /**
     * Delete multiple records by IDs
     * 
     * @param array $ids Array of IDs to delete
     * @return int Number of affected rows
     * @throws \PDOException When database error occurs
     */
    public function deleteWhereIn(array $values, string $column): int
    {
        $this->query = "";
        try {
            if (empty($values)) {
                return 0;
            }

            $sql = sprintf(
                "DELETE FROM %s WHERE %s IN (%s)",
                $this->getTable(),
                $column,
                implode(', ', $values)
            );
            
            $stmt = $this->getDb()->prepare($sql);
            $this->query = $sql;
            
            // Bind all parameters
            foreach ($values as $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($value, $value, $paramType);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \PDOException("Delete multiple operation failed: " . $e->getMessage());
        }
    }
    /**
     * Delete multiple records by IDs
     * 
     * @param array $ids Array of IDs to delete
     * @return int Number of affected rows
     * @throws \PDOException When database error occurs
     */
    public function deleteWhere(array $conditions): int
    {
        $this->query = "";
        try {
            if (empty($conditions)) {
                return 0;
            }

            $sql = sprintf(
                "DELETE FROM %s WHERE %s",
                $this->getTable(),
                implode(' AND ', array_map(fn($column, $value) => "`$column` = :$column", array_keys($conditions), $conditions))
            );
            
            $stmt = $this->getDb()->prepare($sql);
            $this->query = $sql;
            
            // Bind all parameters
            foreach ($conditions as $column => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($column, $value, $paramType);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \PDOException("Delete multiple operation failed: " . $e->getMessage());
        }
    }

    /**
     * Find a record by its primary key
     * 
     * @param mixed $id
     * @return static|null
     */
    public function find($id): ?self
    {
        $this->where($this->getPrimaryKey(), '=', $id);
        $result = $this->executeQuery($this->getQuery(false));
        if (!empty($result)) {
            $this->set($result[0]);
            return $this;
        }
        return null;
    }
    public function first(): ?self
    {
        $result = $this->findAll(false);
        if (!empty($result)) {
            $this->set($result[0]);
            return $this;
        }
        return null;
    }
    /**
     * Find all records
     * 
     * @return array|null
     */
    public function findAll($groupBy = true): ?array
    {
        $result = $this->executeQuery($this->getQuery($groupBy));
        return $result;
    }
     /**
     * Find a record by an array of conditions
     * 
     * @param array $conditions
     * @return TEntity|null
     */
    public function findOneBy(array $conditions): ?object
    {
        foreach ($conditions as $column => $value) {
            $this->where($column, '=', $value);
        }
        return $this->first();
    }
    /**
     * Find a record by an array of conditions
     * 
     * @param array $conditions
     * @return array|null
     */
    public function findBy(array $conditions): ?array
    {
        foreach ($conditions as $column => $value) {
            $this->where($column, '=', $value);
        }
        return $this->findAll();
    }
    /**
     * Find a record by its primary key or fail
     * 
     * @param mixed $id
     * @return static
     * @throws \Exception
     */
    public function findOrFail($id): ?self
    {
        $result = $this->find($id);
        if (empty($result)) {
            throw new \Exception("Model not found");
        }   
        return $result;
    }
    /**
     * Add a where condition to the query
     */
    public function where(string $column, string $operator = '=', mixed $value = null, string $boolean = 'AND'): self
    {
        // if ($value === null) {
        //     $value = $operator;
        //     $operator = '=';
        // }

        // Add table alias to column if it doesn't already have one
        if (strpos($column, '.') === false) {
            $column = "{$this->getTableAlias()}.{$column}";
        }

        $paramKey = ':' . str_replace('.', '_', $column);
        $condition = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
            'paramKey' => $paramKey
        ];

        $this->whereConditions[] = $condition;

        return $this;
    }

    
    /**
     * Add a where condition to the query
     */
    public function whereJson(string $column, string $path, mixed $value, string $boolean = 'AND'): self
    {
        //JSON_EXTRACT(`media`.`file`, '$.path') = 'media/Images/Chairs'
        if (strpos($column, '.') === false) {
            $column = "{$this->getTableAlias()}.{$column}";
        }

        $paramKey = ':' . str_replace('.', '_', $column).'_'.str_replace('$', '_', $path);
        $condition = [
            'column' => "JSON_EXTRACT({$column}, '$.{$path}')",
            'operator' => '=',
            'value' => $value,
            'boolean' => $boolean,
            'paramKey' => $paramKey
        ];

        $this->whereConditions[] = $condition;

        return $this;
    }

    

    /**
     * Add an OR where condition to the query
     */
    public function orWhere(string $column, string $operator = '=', mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a where IN condition to the query
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (empty($values)) {
            return $this;
        }

        // Add table alias to column if it doesn't already have one
        if (strpos($column, '.') === false) {
            $column = "{$this->getTableAlias()}.{$column}";
        }

        $placeholders = [];
        foreach ($values as $i => $value) {
            $paramKey = ':' . str_replace('.', '_', $column) . '_' . $i;
            $paramKey = str_replace('`', '', $paramKey);
            $placeholders[] = $paramKey;
            $this->params[$paramKey] = $value;
        }

        $condition = [
            'column' => $column,
            'operator' => 'IN',
            'placeholders' => '(' . implode(',', $placeholders) . ')',
            'boolean' => $boolean
        ];

        $this->whereConditions[] = $condition;

        return $this;
    }
    /**
     * Add a where IN condition to the query
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (empty($values)) {
            return $this;
        }

        // Add table alias to column if it doesn't already have one
        if (strpos($column, '.') === false) {
            $column = "{$this->getTableAlias()}.{$column}";
        }

        $placeholders = [];
        foreach ($values as $i => $value) {
            $paramKey = ':' . str_replace('.', '_', $column) . '_' . $i;
            $placeholders[] = $paramKey;
            $paramKey = str_replace('`', '', $paramKey);
            $this->params[$paramKey] = $value;
        }

        $condition = [
            'column' => $column,
            'operator' => 'NOT IN',
            'placeholders' => '(' . implode(',', $placeholders) . ')',
            'boolean' => $boolean
        ];

        $this->whereConditions[] = $condition;

        return $this;
    }
    /**
     * Add a where LIKE condition to the query
     */
    public function whereLike(string $column, string $value, string $boolean = 'AND'): self
    {
        return $this->where($column, 'LIKE', "%{$value}%", $boolean);
    }

    /**
     * Add a where NULL condition to the query
     */
    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        // Add table alias to column if it doesn't already have one
        if (strpos($column, '.') === false) {
            $column = "{$this->getTableAlias()}.{$column}";
        }

        $condition = [
            'column' => $column,
            'operator' => 'IS NULL',
            'boolean' => $boolean
        ];

        $this->whereConditions[] = $condition;

        return $this;
    }

    /**
     * Add a join clause to the query
     */
    public function join(string $table, string $first, string $operator = '=', ?string $second = null, string $type = 'LEFT'): self
    {
        // Add table alias to first if it doesn't already have one
        if (strpos($first, '.') === false) {

            $first = "{$this->getTableAlias()}.{$first}";
        }

        // Add table alias to second if it doesn't already have one and is provided
        if ($second !== null && strpos($second, '.') === false) {
            $second = "{$table}.{$second}";
        }
        if(!str_contains($table, '`')){
            $table = "`{$table}`";
        }
        $exist = false;
        foreach ($this->joins as $item) {
            if (str_replace('`', '', $item['table']) === str_replace('`', '', $table) 
                && str_replace('`', '', $item['first']) === str_replace('`', '', $first) 
                && str_replace('`', '', $item['operator']) === str_replace('`', '', $operator) 
                && str_replace('`', '', $item['second']) === str_replace('`', '', $second) 
                && str_replace('`', '', $item['type']) === str_replace('`', '', $type)) {
                $exist = true;
                break;
            }
        }
        if($exist === false){
            $this->joins[] = [
                'table' => $table,
                'aliased' => $table,
                'first' => $first,
                'operator' => $operator,
                'second' => $second,
                'type' => $type
            ];
        }

        

        return $this;
    }
    /**
     * Add a group by clause to the query
     */
    public function groupBy(string $column): self
    {
        $this->groupBy[] = $column;
        return $this;
    }
    /**
     * Add an order by clause to the query
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        // Add table alias to column if it doesn't already have one
        if (strpos($column, '.') === false) {
            $column = "{$this->getTableAlias()}.{$column}";
        }

        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function countAll(): int
    {
        $this->select(["COUNT({$this->getTableAlias()}.{$this->getPrimaryKey()}) as total_records"]);
        $sql = $this->buildCountQuery();
        $result = $this->executeQuery($sql);
        return $result[0]['total_records']??0;
    }

    /**
     * Add a limit clause to the query
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Add an offset clause to the query
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    public function select(array $columns): self
    {
        $alias = $this->getTableAlias();
        if(isset($this->columns[$alias]['columns'])){
            $this->columns[$alias]['columns'] = $columns;
        }
        else{
            $columns = $this->prepareColumns($columns, $alias);
            $this->columns[$alias] = [
                'columns' => $columns,
                'type' => 'none',
                'tableAlias' => $alias
            ];
        }
        return $this;
    }

    public function backupSelect(array $columns): self
    {
        $tableAlias = $this->getTableAlias();
        $existingColumns = $this->columns;
        $newColumns = [];
        array_walk($columns, function($column, $key) use ($tableAlias, $existingColumns, &$newColumns) {
            if (is_string($column)) {
                $column = trim($column);
                $newColumns[$key] = strpos($column, '.') === false ? "{$tableAlias}.{$column}" : $column;
            } else {
                if (is_array($column) && isset($existingColumns[$key]) && isset($existingColumns[$key]['columns'])) {
                    $newColumns[$key] = [
                        'columns' => $column,
                        'type' => $existingColumns[$key]['type'],
                        'field' => $key
                    ];
                } else if(is_array($column)){
                    $newColumns[$key] = ['columns' => $column];
                }
                else {
                    $newColumns[$key] = $column;
                }
            }
        });
        $this->columns = array_merge($existingColumns, $newColumns);
        $this->select = implode(',', array_map(function($column, $key) use ($tableAlias) {
            if(is_string($column)){
                $column = trim($column);
                return strpos($column, '.') === false ? "{$tableAlias}.{$column}" : $column;
            }else{
                if(is_array($column) && isset($column['columns']) && isset($column['type'])){
                    $column['field'] = $key;
                    return $this->processColumnRecursively($column);
                }
                else if(is_array($column) && isset($column['columns'])){
                    return $this->processColumnRecursively($column);
                }
                else{
                    return $column;
                }
            }
        }, $this->columns, array_keys($this->columns)));
        return $this;
    }

    public function getQuery($groupBy = true): string
    {
        $this->buildQuery($groupBy);
        return $this->query;
    }

    public function getQueryString(): string
    {
        return $this->query;
    }

    /**
     * Build the base query with relationships
     */
    public function buildQuery($groupBy = true): self
    {
        $this->query = "";
        $this->setQuery("");
        $tableAlias = $this->getTableAlias();

        //1 Check if in the columns array the columns with main tableAlias are present
        if(!isset($this->columns[$tableAlias]) 
        || (isset($this->columns[$tableAlias]) && empty($this->columns[$tableAlias]))){
            $this->columns[$tableAlias] = [
                'columns' => $this->getTableColumns($this->getTable(), $tableAlias),
                'type' => 'none',
                'tableAlias' => $tableAlias
            ];
        }
        
       
        
        // 2. Build WHERE clause
        $whereClause = '';
        if (!empty($this->whereConditions)) {
            $whereParts = [];
            foreach ($this->whereConditions as $key => $condition) {
                if($key == 0){
                    $wherePart = "{$condition['column']} {$condition['operator']}";
                }
                else{
                    $wherePart = "{$condition['boolean']} {$condition['column']} {$condition['operator']}";
                }
                
                if (isset($condition['value'])) {
                    $paramKey = str_replace('`', '', $condition['paramKey']);
                    $wherePart .= " {$paramKey}";
                    $this->params[$paramKey] = $condition['value'];
                } elseif (isset($condition['placeholders'])) {
                    $wherePart .= " {$condition['placeholders']}";
                }
                
                $whereParts[] = $wherePart;
            }
            
            $whereClause = ' WHERE ' . implode(' ', $whereParts);
        }
        
        // 3. Build JOIN clauses
        $joinClauses = [];
        
        // Add direct joins
        foreach ($this->joins as $key => $join) {
            if(!empty($join) && isset($join['aliased']) && $join['aliased']){
                $joinClauses[] = "{$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
            else if(is_array($join)){
                $this->processJoinItem($join, $joinClauses);
            }
            else{
                $tableAliasOne = $this->getTableAlias();
                $tableAliasTwo = $join['table'];
                $tableOneColumn = str_contains($join['first'], '.') ? $join['first'] : "{$tableAliasOne}.{$join['first']}";
                $tableTwoColumn = str_contains($join['second'], '.') ? $join['second'] : "{$tableAliasTwo}.{$join['second']}";
                $joinClauses[] = "{$join['type']} JOIN {$join['table']} AS {$tableAliasTwo} ON {$tableOneColumn} {$join['operator']} {$tableTwoColumn}";
            }
        }
        foreach ($this->columns as $key => $column) {
            if (is_string($key) && !empty($column)) {
                if($this->getTableAlias() == $key){
                    $selectClauses[] = $this->processColumn($column['columns'], $column['tableAlias']);
                }else{
                    $selectClauses[] = $this->processColumnRecursively($column, $key);
                }
            }
        }
        
        // 5. Build the final query
        $this->query = "SELECT " . implode(', ', $selectClauses) . 
                      " FROM {$this->getTable()} AS {$tableAlias}";
        
        if (!empty($joinClauses)) {
            $this->query .= ' ' . implode(' ', $joinClauses);
        }
        
        $this->query .= $whereClause;

        if($groupBy){
            if (!empty($this->groupBy)) {
                $this->query .= ' GROUP BY ' . implode(', ', $this->groupBy);
            }else{
                $this->query .= ' GROUP BY ' . $this->getTableAlias().'.'. $this->getPrimaryKey();
            }
        }
        
        if (!empty($this->orderBy)) {
            if(is_string($this->orderBy)){
                $this->query .= $this->orderBy;
            }else{
                $this->query .= ' ORDER BY ' . implode(', ', $this->orderBy);
            }
        }
        
        if ($this->limitValue > 0) {
            $this->query .= " LIMIT {$this->limitValue}";
        }
        
        if ($this->offsetValue > 0) {
            $this->query .= " OFFSET {$this->offsetValue}";
        }
        
        return $this;
    }

     /**
     * Build the base query with relationships
     */
    public function buildCountQuery($groupBy = false): string
    {
        $query = "";
        $tableAlias = $this->getTableAlias();
        
        // 1. Build base SELECT query

        // Add main table select
        $selectClauses = ["COUNT({$tableAlias}.{$this->getPrimaryKey()}) as total_records"];
        
        // 2. Build WHERE clause
        // 2. Build WHERE clause
        $whereClause = '';
        if (!empty($this->whereConditions)) {
            $whereParts = [];
            foreach ($this->whereConditions as $key => $condition) {
                if($key == 0){
                    $wherePart = "{$condition['column']} {$condition['operator']}";
                }
                else{
                    $wherePart = "{$condition['boolean']} {$condition['column']} {$condition['operator']}";
                }
                
                if (isset($condition['value'])) {
                    $paramKey = str_replace('`', '', $condition['paramKey']);
                    $wherePart .= " {$paramKey}";
                    $this->params[$paramKey] = $condition['value'];
                } elseif (isset($condition['placeholders'])) {
                    $wherePart .= " {$condition['placeholders']}";
                }
                
                $whereParts[] = $wherePart;
            }
            
            $whereClause = ' WHERE ' . implode(' ', $whereParts);
        }
        
        // 3. Build JOIN clauses
        $joinClauses = [];
        
        // Add direct joins
       // Add direct joins
       foreach ($this->joins as $key => $join) {
            if(!empty($join) && isset($join['aliased']) && $join['aliased']){
                $joinClauses[] = "{$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
            else if(is_array($join)){
                $this->processJoinItem($join, $joinClauses);
            }
            else{
                $tableAliasOne = $this->getTableAlias();
                $tableAliasTwo = $join['table'];
                $tableOneColumn = str_contains($join['first'], '.') ? $join['first'] : "{$tableAliasOne}.{$join['first']}";
                $tableTwoColumn = str_contains($join['second'], '.') ? $join['second'] : "{$tableAliasTwo}.{$join['second']}";
                $joinClauses[] = "{$join['type']} JOIN {$join['table']} AS {$tableAliasTwo} ON {$tableOneColumn} {$join['operator']} {$tableTwoColumn}";
            }
        }
        // 5. Build the final query
        $query = "SELECT " . implode(', ', $selectClauses) . 
                      " FROM {$this->getTable()} AS {$tableAlias}";
        
        if (!empty($joinClauses)) {
            $query .= ' ' . implode(' ', $joinClauses);
        }
        
        $query .= $whereClause;

        if($groupBy){
            if (!empty($this->groupBy)) {
                $query .= ' GROUP BY ' . implode(', ', $this->groupBy);
            }else{
                $query .= ' GROUP BY ' . $this->getPrimaryKey();
            }
        }
        
        return $query;
    }

     /**
     * Execute the query and get the results
     */
    public function executeQuery(string $sql): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $this->bindParams($stmt, $this->params);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    public function bindParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
    }

    //This method will return hasOne or belongs to childrent object to related object model 
    // For example if the relation is admin the object property name amdin_data
    //IN that case admin_data will convert into app/Core/Models/Admin Object
    public function convertJsonObjectToModel()
    {
        foreach ($this->withRelations as $relation) {
            // $property = "{$relation}_data";
            $property = $relation;
            if (property_exists($this, $property) && !empty($this->$property)){
                $data = json_decode($this->$property, true);
                if ($data && isset($this->relationCache[$relation]['class'])) {
                    $modelClass = $this->relationCache[$relation]['class'];
                    if (class_exists($modelClass)) {
                        $modelInstance = new $modelClass();
                        $modelInstance->set($data);
                        $this->$property = $modelInstance; // Assign the instance back to the property
                    }
                }
            }
        }
        return $modelInstance;
    }


    //This method will return hasMany or belongsToMany to childrent object to related object model 
    // For example if the relation is admin the object property name amdin_data
    //IN that case admin_data will convert into app/Core/Models/PostContent Object
    // Create Illuminate Collection which will contain multiple POstContent object
    public function convertJsonArrayToModelCollection()
    {
        foreach ($this->withRelations as $relation) {
            // $property = "{$relation}_data";
            $property = $relation;
            if (property_exists($this, $property) && !empty($this->$property)) {
                $dataArray = json_decode($this->$property, true);
                if (is_array($dataArray) && isset($this->relationCache[$relation]['class'])) {
                    $modelClass = $this->relationCache[$relation]['class'];
                    $collection = [];
                    foreach ($dataArray as $data) {
                        if (class_exists($modelClass)) {
                            $modelInstance = new $modelClass();
                            $modelInstance->set($data);
                            $collection[] = $modelInstance;
                        }
                    }
                    $collection = collect($collection);
                    $this->$property = $collection; // Assign the collection back to the property
                }
            }
        }
        return $collection; // Return the current instance
    }

    private function processJoinItem($joinItem, array &$joinClauses): void
    {
        if (empty($joinItem)) {
            return;
        }

        if (isset($joinItem['aliased']) && $joinItem['aliased']) {
            $joinClauses[] = "{$joinItem['type']} JOIN {$joinItem['table']} ON {$joinItem['first']} {$joinItem['operator']} {$joinItem['second']}";
            return;
        }

        if (is_array($joinItem)) {
            foreach ($joinItem as $item) {
                $this->processJoinItem($item, $joinClauses);
            }
        }
    }
}
