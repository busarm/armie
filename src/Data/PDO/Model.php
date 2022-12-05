<?php

namespace Busarm\PhpMini\Data\PDO;

use Busarm\PhpMini\Data\PDO\Connection;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Helpers\StringableDateTime;

use function Busarm\PhpMini\Helpers\is_list;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class Model extends BaseDto
{
    const EVENT_BEFORE_CREATE = 'before_create';
    const EVENT_AFTER_CREATE = 'after_create';
    const EVENT_BEFORE_UPDATE = 'before_update';
    const EVENT_AFTER_UPDATE = 'after_update';
    const EVENT_BEFORE_DELETE = 'before_delete';
    const EVENT_AFTER_DELETE = 'after_delete';

    protected array $events = [
        self::EVENT_BEFORE_CREATE => [],
        self::EVENT_AFTER_CREATE => [],
        self::EVENT_BEFORE_UPDATE => [],
        self::EVENT_AFTER_UPDATE => [],
        self::EVENT_BEFORE_DELETE => [],
        self::EVENT_AFTER_DELETE => [],
    ];

    /**
     * Database connection instance
     *
     * @var Connection
     */
    protected Connection $db;

    /**
     * Max number of items to return in list
     *
     * @var integer
     */
    protected int $perPage = 20;

    /**
     * Model is new - not saved yet
     *
     * @var boolean
     */
    protected bool $isNew = true;

    /**
     * Auto populate relations
     *
     * @var boolean
     */
    protected bool $autoLoadRelations = true;

    /**
     * Loaded relations
     *
     * @var array
     */
    protected array $loadedRelations = [];

    final public function __construct(Connection $db = null)
    {
        $this->db = $db ?? Connection::make();
        $this->setUp();
    }

    /**
     * Set up model.
     * Override to add constumizations when model is initialized
     */
    public function setUp()
    {
        if (!empty($this->getCreatedDateName())) {
            if (!empty($this->getUpdatedDateName())) {
                $this->listen(self::EVENT_BEFORE_CREATE, fn () => $this->{$this->getCreatedDateName()} = $this->{$this->getUpdatedDateName()} = new StringableDateTime);
            } else {
                $this->listen(self::EVENT_BEFORE_CREATE, fn () => $this->{$this->getCreatedDateName()} = new StringableDateTime);
            }
        }
        if (!empty($this->getUpdatedDateName())) {
            $this->listen(self::EVENT_BEFORE_UPDATE, fn () => $this->{$this->getUpdatedDateName()} = new StringableDateTime);
        }
    }

    /**
     * Get the value of db
     */
    public function getDb(): Connection
    {
        return $this->db;
    }

    /**
     * Set the value of perPage.
     * Pagination limit per page.
     *
     * @return  self
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Get the value of perPage.
     * Pagination limit per page.
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the value of isNew.
     * Model is new - data hasn't been saved.
     *
     * @return  self
     */
    public function setIsNew(bool $isNew)
    {
        $this->isNew = $isNew;

        return $this;
    }

    /**
     * Get the value of isNew.
     * Model is new - data hasn't been saved.
     */
    public function getIsNew()
    {
        return $this->isNew;
    }

    /**
     * Get the value of autoLoadRelations.
     */
    public function getAutoLoadRelations()
    {
        return $this->autoLoadRelations;
    }

    /**
     * Set the value of autoLoadRelations.
     *
     * @return  self
     */
    public function setAutoLoadRelations(bool $autoLoadRelations)
    {
        $this->autoLoadRelations = $autoLoadRelations;

        return $this;
    }

    /**
     * Model table name. e.g db table, collection name
     *
     * @return string
     */
    abstract public function getTableName(): string;

    /**
     * Model key name. e.g table primary key, unique index
     *
     * @return string|null
     */
    abstract public function getKeyName(): ?string;

    /**
     * Model relations.
     *
     * @return \Busarm\PhpMini\Data\PDO\Relation[]
     */
    abstract public function getRelations(): array;

    /**
     * Model fields
     *
     * @return \Busarm\PhpMini\Data\PDO\Field[]
     */
    abstract public function getFields(): array;

    /**
     * Model relations names.
     *
     * @return array<string>
     */
    public function getRelationNames(): array
    {
        return array_map(fn ($relation) => strval($relation), $this->getRelations());
    }

    /**
     * Model field names. Exclude relation names.
     *
     * @return array<string>
     */
    public function getFieldNames(): array
    {
        $fieldNames = array_map(fn ($field) => strval($field), $this->getFields());
        $relationNames = $this->getRelationNames();
        return array_diff($fieldNames, $relationNames);
    }

    /**
     * Model created date param name. e.g created_at, createdAt
     *
     * @return string
     */
    public function getCreatedDateName(): ?string
    {
        return null;
    }

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt
     *
     * @return string
     */
    public function getUpdatedDateName(): ?string
    {
        return null;
    }

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt
     *
     * @return string
     */
    public function getSoftDeleteDateName(): ?string
    {
        return null;
    }

    /**
     * Check if model was soft deleted
     * 
     * @return bool
     */
    public function isTrashed(): bool
    {
        return !empty($this->getSoftDeleteDateName()) && isset($this->{$this->getSoftDeleteDateName()});
    }


    /**
     * Count total number of model items.
     *
     * @param string|null $query Custom query to count
     * @param array $params Custom query params
     * @return integer
     */
    public function count(string|null $query = null, $params = array()): int
    {
        $query = $query ? $this->getDb()->applyCount($query) : sprintf("SELECT COUNT(*) FROM %s", $this->getTableName());
        if ($query) {
            $stmt = $this->getDb()->prepare($query);
            if ($stmt && $stmt->execute($params) && ($result = $stmt->fetchColumn())) {
                return intval($result);
            }
        }
        return 0;
    }

    /**
     * Find model for id. Without trashed (deleted) models
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public function find($id, $conditions = [], $params = [], $columns = []): ?self
    {
        if (!empty($this->getSoftDeleteDateName())) {
            return $this->findWhere(array_merge($conditions, [
                $this->getKeyName() => ':id',
                sprintf("ISNULL(%s)", $this->getSoftDeleteDateName())
            ]), array_merge($params, [
                ':id' => $id
            ]), $columns);
        } else {
            return $this->findWhere(array_merge($conditions, [
                $this->getKeyName() => ':id',
            ]), array_merge($params, [
                ':id' => $id
            ]), $columns);
        }
    }

    /**
     * Find model for id. With trashed (deleted) models
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public function findTrashed($id, $conditions = [], $params = [], $columns = []): ?self
    {
        return $this->findWhere(array_merge($conditions, [
            $this->getKeyName() => ':id'
        ]), array_merge($params, [
            ':id' => $id
        ]), $columns);
    }

    /**
     * Find model with condition.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public function findWhere($conditions = [], $params = [], $columns = []): ?self
    {
        if (empty($columns)) $columns = ["*"];

        $colsPlaceHolders = $this->parseColumns($columns);
        $condPlaceHolders = $this->parseConditions($conditions);

        $stmt = $this->db->prepare(sprintf(
            "SELECT %s FROM %s %s LIMIT 1",
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? 'WHERE ' . $condPlaceHolders : ''
        ));

        if ($stmt && $stmt->execute($params) && ($result = $stmt->fetch(Connection::FETCH_ASSOC))) {
            return self::withEvent($result, $this->events)
                ->setIsNew(false)
                ->setPerPage($this->perPage)
                ->setAutoLoadRelations($this->autoLoadRelations)
                ->processAutoLoadRelations()
                ->select($this->mergeColumnsRelations(!empty($this->selectedAttrs) && !in_array('*', $this->selectedAttrs) ? $this->selectedAttrs : $columns));
        }
        return null;
    }

    /**
     * Get list of model. Without trashed (deleted) models
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names. 
     * @return self[]
     */
    public function all($conditions = [], $params = [], $columns = []): array
    {
        if (!empty($this->getSoftDeleteDateName())) {
            return $this->allTrashed(array_merge($conditions, [
                sprintf("ISNULL(%s)", $this->getSoftDeleteDateName())
            ]), $params, $columns);
        } else {
            return $this->allTrashed($conditions, $params, $columns);
        }
    }

    /**
     * Get list of model. With trashed (deleted) models
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names. 
     * @return self[]
     */
    public function allTrashed($conditions = [], $params = [], $columns = []): array
    {
        if (empty($columns)) $columns = ["*"];

        $colsPlaceHolders = $this->parseColumns($columns);
        $condPlaceHolders = $this->parseConditions($conditions);

        $stmt = $this->db->prepare(sprintf(
            "SELECT %s FROM %s %s LIMIT %s",
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? 'WHERE ' . $condPlaceHolders : '',
            $this->perPage
        ));

        if ($stmt && $stmt->execute($params) && $results = $stmt->fetchAll(Connection::FETCH_ASSOC)) {
            return array_map(fn ($result) => self::withEvent($result, $this->events)
                ->setIsNew(false)
                ->setPerPage($this->perPage)
                ->setAutoLoadRelations($this->autoLoadRelations)
                ->processAutoLoadRelations()
                ->select($this->mergeColumnsRelations(!empty($this->selectedAttrs) && !in_array('*', $this->selectedAttrs) ? $this->selectedAttrs : $columns)), $results);
        }

        return [];
    }

    /**
     * Delete model. 
     *
     * @param bool $force Force permanent delete or soft delete if supported
     * @return bool
     */
    public function delete($force = false): bool
    {
        // Soft delele
        if (!$force && !empty($this->getSoftDeleteDateName())) {
            $this->{$this->getSoftDeleteDateName()} = new StringableDateTime;
            return $this->save() !== false;
        }

        // Permanent delete
        else {

            $this->emit(self::EVENT_BEFORE_DELETE);

            $stmt = $this->db->prepare(sprintf(
                "DELETE FROM %s WHERE %s = ?",
                $this->getTableName(),
                $this->getKeyName()
            ));
            if ($stmt) {
                $stmt->execute([$this->{$this->getKeyName()}]);
                if ($stmt->rowCount() > 0) {
                    $this->emit(self::EVENT_AFTER_DELETE);
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Restore model
     * @return bool
     */
    public function restore(): bool
    {
        if ($this->getSoftDeleteDateName() && isset($this->{$this->getSoftDeleteDateName()})) {
            $this->{$this->getSoftDeleteDateName()} = NULL;
            return $this->save() !== false;
        }
        return false;
    }

    /**
     * Save model
     * 
     * @param bool $trim Exclude NULL properties before saving
     * @return bool
     */
    public function save($trim = false): bool
    {
        // Create
        if ($this->isNew || !isset($this->{$this->getKeyName()})) {

            $this->emit(self::EVENT_BEFORE_CREATE);

            $params = $this->select($this->getFieldNames())->toArray($trim);
            if (empty($params)) return false;

            $placeHolderKeys = implode(',', array_keys($params));
            $placeHolderValues = implode(',', array_fill(0, count($params), '?'));
            $stmt = $this->db->prepare(sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->getTableName(),
                $placeHolderKeys,
                $placeHolderValues
            ));

            if (!$stmt || !$stmt->execute(array_values($params))) return false;

            // Update id for Auto Increment
            if (!isset($this->{$this->getKeyName()}) && !empty($id = $this->db->lastInsertId())) {
                $this->{$this->getKeyName()} = $id;
            }

            $this->emit(self::EVENT_AFTER_CREATE);
        }

        // Update
        else {

            $this->emit(self::EVENT_BEFORE_UPDATE);

            $params = $this->select($this->getFieldNames())->toArray($trim);
            if (empty($params)) return false;

            $placeHolder = implode(',', array_map(fn ($key) => "$key = ?", array_keys($params)));
            $stmt = $this->db->prepare(sprintf(
                "UPDATE %s SET %s WHERE %s = ?",
                $this->getTableName(),
                $placeHolder,
                $this->getKeyName()
            ));

            if (!$stmt || !$stmt->execute([...array_values($params), $this->{$this->getKeyName()}])) return false;

            $this->emit(self::EVENT_AFTER_UPDATE);
        }

        $this->isNew = false;

        return true;
    }

    /**
     * Load relations if auto load relations enabled
     *
     * @return self
     */
    public function processAutoLoadRelations(): self
    {
        return $this->autoLoadRelations ? $this->loadRelations() : $this;
    }

    /**
     * Load relations
     *
     * @param array $conditions
     * @param array $params
     * @param array $columns
     * @return self
     */
    public function loadRelations(array $conditions = [], array $params = [], array $columns = ['*']): self
    {
        // TODO Implement proper eager of relations - Load all relations in one query

        foreach ($this->getRelations() as $relation) {
            $this->{$relation->getName()} = $relation->get($conditions, $params, $columns);
            $this->loadedRelations[] = $relation->getName();
        }

        return $this;
    }

    /**
     * Load single relation by name
     *
     * @param string $name
     * @param array $conditions
     * @param array $params
     * @param array $columns
     * @return self
     */
    public function loadRelation(string $name, array $conditions = [], array $params = [], array $columns = ['*']): self
    {
        foreach ($this->getRelations() as $relation) {
            if (strtolower($name) === strtolower($relation->getName())) {
                $this->{$relation->getName()} = $relation->get($conditions, $params, $columns);
                $this->loadedRelations[] = $relation->getName();
                return $this;
            }
        }

        return $this;
    }

    /**
     * Merge columns with relation names
     *
     * @param array $columns
     * @return array
     */
    public function mergeColumnsRelations(array $columns): array
    {
        if ($this->autoLoadRelations) {
            return array_unique([...(is_list($columns) ? $columns : array_keys($columns)), ...$this->getRelationNames()]);
        } else if (!empty($this->loadedRelations)) {
            return array_unique([...(is_list($columns) ? $columns : array_keys($columns)), ...$this->loadedRelations]);
        }
        return is_list($columns) ? $columns : array_keys($columns);
    }

    /**
     * Parse query colomns
     *
     * @param array $columns
     * @return string
     */
    public function parseColumns(array $columns): string
    {
        $cols = [];
        $relationCols = [];
        $validCols = array_keys($this->attributes(false));

        // If all cols not selected:
        // Always include relation cols
        // Always exclude relation names from valid cols
        if (!in_array('*', $columns)) {
            foreach ($this->getRelations() as $relation) {
                $validCols = array_diff($validCols, [$relation->getName()]);
                $relationCols = array_keys($relation->getReferences());
                $columns = array_merge($columns, $relationCols); // Add relationship cols
                $columns = array_intersect($columns, $validCols); // Remove cols not in valid cols
            }
        }

        foreach ($columns as $key => $col) {
            if (!str_starts_with($col, '-')) {
                $cols[] = is_numeric($key) ? $col : sprintf("%s AS %s", $key, $col);
            }
        }
        return implode(',', $cols);
    }

    /**
     * Parse query conditions
     *
     * @param array $conditions
     * @return string
     */
    public function parseConditions(array $conditions): string
    {
        $result = null;

        $parseCondtionalArray = function ($result, $key, $cond) {
            $key = strtoupper($key);
            return  $result ?
                $result . sprintf(" %s %s", in_array($key, ['AND', 'OR']) ? $key : 'AND', $this->parseConditions($cond)) :
                sprintf("%s %s", $key == 'NOT' ? $key : '', $this->parseConditions($cond));
        };
        $parseArrayList = function ($result, $key, $cond) {
            return $result ?
                $result . " AND " . sprintf("%s IN (%s)", $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond))) :
                sprintf("%s IN (%s)", $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond)));
        };
        $parseArray = function ($result, $cond) {
            return  $result ?
                $result . " AND " . sprintf("%s", $this->parseConditions($cond)) :
                sprintf("%s", $this->parseConditions($cond));
        };

        $parseString = function ($result, $cond) {
            return $result ?
                $result . " AND " . sprintf("(%s)", $cond) :
                sprintf("(%s)", $cond);
        };
        $parseKeyedString = function ($result, $key, $cond) {
            // Key is a conditional operator 
            if (in_array(strtoupper($key), ['AND', 'OR'])) {
                return $result ?
                    $result  . sprintf(" %s (%s)", strtoupper($key), $cond) :
                    sprintf("(%s)", $cond);
            }
            // Key is a conditional (NOT) operator 
            if (strtoupper($key) == 'NOT') {
                return $result ?
                    $result  . sprintf("(%s)", $cond) :
                    sprintf("%s (%s)", $key, $cond);
            }
            // Key is a parameter
            else {
                return $result ?
                    $result . " AND " . sprintf("%s = %s", $key, $this->escapeCond($cond)) :
                    sprintf("%s = %s", $key, $this->escapeCond($cond));
            }
        };


        if (!empty($conditions)) {

            // List type ([a,b,c])
            if (is_list($conditions)) {
                foreach ($conditions as $cond) {
                    if (is_array($cond)) {
                        $result = $parseArray($result, $cond);
                    } else {
                        $result = $parseString($result, $cond);
                    }
                }
            }
            // Key value type ([a=>1, b=>2, c=>3])
            else {
                foreach ($conditions as $key => $cond) {
                    if (is_array($cond)) {
                        // List type ([key => [a,b,c]])
                        if (is_list($cond)) {
                            $result = $parseArrayList($result, $key, $cond);
                        }
                        // Key value type (['AND/OR' => [a=>1, b=>2, c=>3]])
                        else {
                            $result = $parseCondtionalArray($result, $key, $cond);
                        }
                    } else {
                        // Key not available - only value
                        if (is_numeric($key)) {
                            $result = $parseString($result, $cond);
                        }
                        // Key available
                        else {
                            $result = $parseKeyedString($result, $key, $cond);
                        }
                    }
                }
            }
        }

        return $result ? '(' . $result . ')' : '';
    }

    /**
     * Add quotes to condition if needed
     *
     * @param string $cond
     * @return string
     */
    private function escapeCond(string $cond): string
    {
        return $cond == '?' || str_starts_with($cond, ':') ? $cond : "'$cond'";
    }


    ##### Events #####

    /**
     * Listen to model event
     *
     * @param string $event
     * @param callable $fn
     * @return void
     */
    public function listen(string $event, callable $fn)
    {
        if (isset($this->events[$event])) {
            $this->events[$event][] = $fn;
        }
    }

    /**
     * Trigger model events
     *
     * @param string $event
     * @return void
     */
    public function emit(string $event)
    {
        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $fn) {
                $fn($this);
            }
        }
    }

    ##### Statics #####

    /**
     * Find model for id. Without trashed (deleted) models
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public static function findById($id, $conditions = [], $params = [], $columns = []): ?self
    {
        return (new static)->find($id, $conditions, $params, $columns);
    }

    /**
     * Find model for id. With trashed (deleted) models
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public static function findTrashedById($id, $conditions = [], $params = [], $columns = []): ?self
    {
        return (new static)->findTrashed($id, $conditions, $params, $columns);
    }

    /**
     * Get list of model. Without trashed (deleted) models
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @param int|null $limit Query limit
     * @return self[]
     */
    public static function getAll(array $conditions = [], array $params = [], array $columns = [], int|null $limit = NULL): array
    {
        $model = (new static);
        if ($limit) $model->setPerPage($limit);
        return $model->all($conditions, $params, $columns);
    }

    /**
     * Get list of model. With trashed (deleted) models
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @param int|null $limit Query limit
     * @return self[]
     */
    public static function getAllTrashed(array $conditions = [], array $params = [], array $columns = [], int|null $limit = NULL): array
    {
        $model = (new static);
        if ($limit) $model->setPerPage($limit);
        return $model->allTrashed($conditions, $params, $columns);
    }

    /**
     * Load dto with array of class attibutes
     *
     * @param array|object|null $data
     * @param array $events Map of events. e.g `['EVENT_BEFORE_CREATE' => fn() or [ fn(), fn() ]]`
     * @return static
     */
    public static function withEvent(array|object|null $data, array $events = []): static
    {
        $model = new static;
        if ($data) {
            if ($data instanceof self) {
                $model->load($data->toArray());
            } else {
                $model->load((array)$data);
            }
        }

        $model->events = [];
        foreach ($events as $key => $event) {
            if (is_array($event)) {
                foreach ($event as $e) {
                    $model->listen($key, $e);
                }
            } else {
                $model->listen($key, $event);
            }
        }
        return $model;
    }

    ##### Override #####

    /**
     * @inheritDoc
     */
    public static function with(array|object|null $data): self
    {
        $dto = new static;
        if ($data) {
            if ($data instanceof self) {
                $dto->load($data->toArray());
            } else {
                $dto->load((array)$data);
            }
        }
        return $dto;
    }

    /**
     * @inheritDoc
     */
    public static function withCustom(array|object|null $data): self
    {
        $dto = new static;
        if ($data) $dto->loadCustom((array)$data);
        return $dto;
    }

    /**
     * @inheritDoc
     */
    public function toArray($trim = false): array
    {
        return parent::toArray($trim);
    }

    /**
     * @inheritDoc
     */
    public function attributes($all = false, $trim = false): array
    {
        // print_r($this->getRelationNames());
        if (!empty($fields = $this->getFields())) {
            $attrs = [];
            if ($this->autoLoadRelations) {
                $fields = array_merge($fields, $this->getRelations());
            } else if (!empty($this->loadedRelations)) {
                $fields = array_merge($fields, $this->getRelations());
            }
            foreach ($fields as $field) {
                $attrs[$field->getName()] = $field->getType();
            }
            return $attrs;
        }
        return parent::attributes($all, $trim);
    }
}
