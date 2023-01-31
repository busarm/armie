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
     * Database connection instance.
     *
     * @var Connection
     */
    protected Connection $db;

    /**
     * Max number of items to return in list.
     *
     * @var integer
     */
    protected int $perPage = 20;

    /**
     * Model is new - not saved yet.
     *
     * @var boolean
     */
    protected bool $isNew = true;

    /**
     * Auto populate relations.
     *
     * @var boolean
     */
    protected bool $autoLoadRelations = true;

    /**
     * Loaded relations names.
     *
     * @var array<string>
     */
    protected array $loadedRelations = [];

    /**
     * Requested relations. Only these relation names will loaded if auto load relations not enabled.
     *
     * @var array<string>|array<string,callable>
     */
    protected array $requestedRelations = [];

    final public function __construct(Connection $db = null)
    {
        $this->db = $db ?? Connection::make();
        $this->setUp();
    }

    /**
     * Set up model.
     * Override to add customizations when model is initialized
     */
    public function setUp()
    {
    }

    /**
     * Get the database connection
     */
    public function getDb(): Connection
    {
        return $this->db;
    }

    /**
     * Set pagination limit per page.
     *
     * @return  self
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Get pagination limit per page.
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
     * Set Events
     *
     * @param array $events Map of events. e.g `[Model::EVENT_BEFORE_CREATE => fn() or [ fn(), fn() ]]`
     * @return self
     */
    public function setEvents(array $events = []): self
    {
        $this->events = [];
        foreach ($events as $key => $event) {
            if (is_array($event)) {
                foreach ($event as $e) {
                    $this->listen($key, $e);
                }
            } else {
                $this->listen($key, $event);
            }
        }
        return $this;
    }

    /**
     * Set requested relations
     *
     * @param  array<string>|array<string,callable> $requestedRelations List of relation names or Relation name as key with callback as value. 
     * Only these relation names will loaded if auto load relations not enabled.
     *
     * @return  self
     */
    public function setRequestedRelations(array $requestedRelations)
    {
        $this->requestedRelations = $requestedRelations;

        return $this;
    }

    /**
     * Set loaded relations names.
     *
     * @param  array<string>  $loadedRelations  Loaded relations names.
     *
     * @return  self
     */
    public function setLoadedRelations(array $loadedRelations)
    {
        $this->loadedRelations = $loadedRelations;

        return $this;
    }

    /**
     * Add loaded relations name.
     *
     * @param  string  $loadedRelation  Loaded relations name.
     *
     * @return  self
     */
    public function addLoadedRelation(string $loadedRelation)
    {
        $this->loadedRelations[] = $loadedRelation;

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
        $fields = $this->getFields();
        $fieldNames = !empty($fields) ? array_map(fn ($field) => strval($field), $fields) : array_keys($this->attributes());
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
            return $this->clone()
                ->load($result)
                ->setIsNew(false)
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
            "SELECT %s FROM %s %s %s",
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? 'WHERE ' . $condPlaceHolders : '',
            $this->perPage >= 0 ? 'LIMIT ' . $this->perPage : ''
        ));

        if ($stmt && $stmt->execute($params) && $results = $stmt->fetchAll(Connection::FETCH_ASSOC)) {
            return $this->processEagerLoadRelations(array_map(fn ($result) => $this->clone()
                ->load($result)
                ->setIsNew(false)
                ->select($this->mergeColumnsRelations(!empty($this->selectedAttrs) && !in_array('*', $this->selectedAttrs) ? $this->selectedAttrs : $columns)), $results));
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
     * @param bool $relations Save relations if available
     * @return bool
     */
    public function save($trim = false, $relations = true): bool
    {
        // Create
        if ($this->isNew || !isset($this->{$this->getKeyName()})) {

            $this->emit(self::EVENT_BEFORE_CREATE);

            // Add created & updated dates if not available
            if (!empty($this->getCreatedDateName()) && !isset($this->{$this->getCreatedDateName()})) {
                $this->{$this->getCreatedDateName()} = new StringableDateTime;
            }
            if (!empty($this->getUpdatedDateName()) && !isset($this->{$this->getUpdatedDateName()})) {
                $this->{$this->getUpdatedDateName()} = new StringableDateTime;
            }

            $params = $this->select($this->getFieldNames())->toArray($trim);
            if (empty($params)) return false;

            $placeHolderKeys = implode(',', array_map(fn ($key) => "`$key`", array_keys($params)));
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

            // Add updated date if not available
            if (!empty($this->getUpdatedDateName()) && !isset($this->{$this->getUpdatedDateName()})) {
                $this->{$this->getUpdatedDateName()} = new StringableDateTime;
            }

            $params = $this->select($this->getFieldNames())->toArray($trim);
            if (empty($params)) return false;

            $placeHolder = implode(',', array_map(fn ($key) => "`$key` = ?", array_keys($params)));
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

        // Save relations if available
        if ($relations) {
            $this->saveRelations();
        }

        return true;
    }

    /**
     * Save relations
     * 
     * @return bool
     */
    protected  function saveRelations(): bool
    {
        $success = true;
        foreach ($this->getRelations() as $relation) {
            $data = $this->{$relation->getName()} ?? null;
            if (isset($data)) {
                $success = !$relation->save($data) ? false : $success;
            }
        }
        return $success;
    }

    /**
     * Perform database transaction. Auto rollback if unsuccessful.
     * 
     * @param callable $callable Return FALSE if unsuccessful
     * @return mixed result of $callable.
     */
    public function transaction(callable $callable)
    {
        $this->db->beginTransaction();
        $result = $callable();
        if ($result === false) {
            $this->db->rollBack();
        }
        $this->db->commit();
        return $result;
    }

    /**
     * Process eager loading of relations.
     *
     * @param self[] $items
     * @return self[]
     */
    public function processEagerLoadRelations(array $items): array
    {
        return $this->autoLoadRelations || !empty($this->requestedRelations) ? $this->eagerLoadRelations($items) : $items;
    }

    /**
     * Process auto loading of relations.
     *
     * @return self
     */
    public function processAutoLoadRelations(): self
    {
        return $this->autoLoadRelations || !empty($this->requestedRelations) ? $this->loadRelations() : $this;
    }

    /**
     * Eager load relations.
     * 
     * @param self[] $items
     * @return self[]
     */
    public function eagerLoadRelations(array $items): array
    {
        $relsIsList = is_list($this->requestedRelations);
        foreach ($this->getRelations() as &$relation) {
            if (empty($this->requestedRelations) || in_array($relation->getName(), $relsIsList ? $this->requestedRelations : array_keys($this->requestedRelations))) {
                // Trigger callback if available
                $callback = $this->requestedRelations[$relation->getName()] ?? null;
                if (!$relsIsList && $callback && is_callable($callback)) {
                    $callback($relation);
                }
                $items = $relation->load($items);
            }
        }

        return $items;
    }

    /**
     * Load relations
     *
     * @return self
     */
    public function loadRelations(): self
    {
        $relsIsList = is_list($this->requestedRelations);
        foreach ($this->getRelations() as &$relation) {
            if (empty($this->requestedRelations) || in_array($relation->getName(), $relsIsList ? $this->requestedRelations : array_keys($this->requestedRelations))) {
                // Trigger callback if available
                $callback = $this->requestedRelations[$relation->getName()] ?? null;
                if (!$relsIsList && $callback && is_callable($callback)) {
                    $callback($relation);
                }
                $this->{$relation->getName()} = $relation->get();
                $this->loadedRelations[] = $relation->getName();
            }
        }

        return $this;
    }

    /**
     * Load single relation by name
     *
     * @param string $name
     * @param callable $callback Anonymous function with `Relation::class` as parameter
     * @return self
     */
    public function loadRelation(string $name, callable $callback = null): self
    {
        foreach ($this->getRelations() as &$relation) {
            if (strtolower($name) === strtolower($relation->getName())) {
                // Trigger callback if available
                if ($callback) $callback($relation);

                $this->{$relation->getName()} = $relation->get();
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
                if ($col === "*") {
                    if (!in_array($col, $cols)) {
                        $cols = [$col];
                        break;
                    }
                } else {
                    $cols[] = is_numeric($key) ? "`$col`" : sprintf("`%s` AS %s", $key, $col);
                }
            }
        }
        return  implode(',', $cols);
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
                $result . " AND " . sprintf("`%s` IN (%s)", $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond))) :
                sprintf("`%s` IN (%s)", $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond)));
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
            else if (strtoupper($key) == 'NOT') {
                return $result ?
                    $result  . sprintf("(%s)", $cond) :
                    sprintf("%s (%s)", $key, $cond);
            }
            // Key is a parameter
            else {
                return $result ?
                    $result . " AND " . sprintf("`%s` = %s", $key, $this->escapeCond($cond)) :
                    sprintf("`%s` = %s", $key, $this->escapeCond($cond));
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
     * Create record
     *
     * @param array $data
     * @return self|null
     */
    public static function create(array $data): ?self
    {
        $model = self::with($data);
        if ($model->save()) {
            return $model;
        }
        return null;
    }

    /**
     * Update record
     *
     * @param string|int $id
     * @param array $data
     * @return self|null
     */
    public static function update(string|int $id, array $data): ?self
    {
        $model = self::findById($id);
        if ($model && $model->load($data)->save()) {
            return $model;
        }
        return null;
    }

    /**
     * Find model for id. Without trashed (deleted) models
     *
     * @param string|int $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public static function findById(string|int $id, array $conditions = [], array $params = [], array $columns = []): ?self
    {
        return (new static)->find($id, $conditions, $params, $columns);
    }

    /**
     * Find model for id. With trashed (deleted) models
     *
     * @param string|int $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public static function findTrashedById(string|int $id, array $conditions = [], array $params = [], array $columns = []): ?self
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


    ##### Clones #####

    /**
     * Clone model
     * 
     * @return Model
     */
    public function clone()
    {
        $model = (clone $this);
        return $model;
    }

    /**
     * Set events to be loaded with model
     *
     * @param array $events
     * @return self
     */
    public function withEvents(array $events): self
    {
        return $this->clone()->setEvents($events);
    }

    /**
     * Set limit to be loaded with model
     *
     * @param int $limit
     * @return self
     */
    public function withLimit(int $limit): self
    {
        return $this->clone()->setPerPage($limit);
    }

    /**
     * Set relations to be loaded with model
     *
     * @param array<string>|array<string,callable> $relations List of relation names or Relation name as key with callback as value.
     * @return self
     */
    public function withRelations(array $relations): self
    {
        return $this->clone()->setRequestedRelations($relations);
    }

    ##### Override #####

    /**
     * @inheritDoc
     */
    public static function with(array|object|null $data, $sanitize = false): self
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
    public static function withCustom(array|object|null $data, $sanitize = false): self
    {
        $dto = new static;
        if ($data) $dto->loadCustom((array)$data);
        return $dto;
    }

    /**
     * @inheritDoc
     */
    public function attributes($all = false, $trim = false): array
    {
        if (!empty($fields = $this->getFields())) {
            $attrs = [];
            if ($this->autoLoadRelations) {
                $fields = array_merge($fields, $this->getRelations());
            } else if (!empty($this->loadedRelations)) {
                $fields = array_merge($fields, array_filter($this->getRelations(), fn ($rel) => in_array(strval($rel), $this->loadedRelations),));
            }
            foreach ($fields as $field) {
                $attrs[$field->getName()] = $field->getType();
            }
            return $attrs;
        }
        return parent::attributes($all, $trim);
    }
}
