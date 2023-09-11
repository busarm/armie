<?php

namespace Armie\Data\PDO;

use Armie\Data\DataObject;
use Armie\Helpers\StringableDateTime;
use Armie\Interfaces\Data\ModelInterface;
use Generator;

use function Armie\Helpers\dispatch;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * // TODO: Load relations concurrently
 * // TODO: Use Attributes for Model Table and Fields and Relations
 */
abstract class Model extends DataObject implements ModelInterface
{
    const EVENT_BEFORE_QUERY = self::class . ':BeforeQuery';
    const EVENT_AFTER_QUERY = self::class . ':AfterQuery';
    const EVENT_BEFORE_CREATE = self::class . ':BeforeCreate';
    const EVENT_AFTER_CREATE = self::class . ':AfterCreate';
    const EVENT_BEFORE_UPDATE = self::class . ':BeforeUpdate';
    const EVENT_AFTER_UPDATE = self::class . ':AfterUpdate';
    const EVENT_BEFORE_DELETE = self::class . ':BeforeDelete';
    const EVENT_AFTER_DELETE = self::class . ':AfterDelete';

    /**
     * Model is new - not saved yet.
     *
     * @var bool
     */
    protected bool $_new = true;

    /**
     * Max number of items to return in list.
     *
     * @var int
     */
    protected int $_perPage = 20;


    /**
     * Auto populate relations.
     *
     * @var bool
     */
    protected bool $_autoLoadRelations = true;

    /**
     * Loaded relations names.
     *
     * @var array<string>
     */
    protected array $_loadedRelations = [];

    /**
     * Requested relations. Only these relation names will loaded if auto load relations not enabled.
     *
     * @var array<string>|array<string,callable>
     */
    protected array $_requestedRelations = [];

    /**
     * Database connection instance.
     *
     * @var Connection|null
     */
    private Connection|null $_db;

    final public function __construct(Connection|null $db = null)
    {
        $this->_db = $db ?? Connection::make();
        $this->boot();
    }

    /**
     * Get properties to be excluded from model's entity fields.
     */
    protected function __excluded(): array
    {
        return array_merge(
            parent::__excluded(),
            [
                '_db', '_autoLoadRelations', '_requestedRelations', '_loadedRelations',
                '_new', '_perPage'
            ]
        );
    }


    public function __sleep(): array
    {
        return array_merge(
            parent::__sleep(),
            [
                '_autoLoadRelations', '_requestedRelations', '_loadedRelations',
                '_new', '_perPage'
            ]
        );
    }

    public function __wakeup(): void
    {
        $this->_db = Connection::make();
    }

    /**
     * Boot up model.
     * Override to add customizations when model is initialized.
     */
    public function boot()
    {
        // Override
    }

    /**
     * Get the database connection.
     */
    public function getDatabase(): Connection
    {
        return $this->_db;
    }

    /**
     * Set pagination limit per page.
     *
     * @return static
     */
    public function setPerPage(int $perPage)
    {
        $this->_perPage = $perPage;

        return $this;
    }

    /**
     * Get pagination limit per page.
     */
    public function getPerPage()
    {
        return $this->_perPage;
    }

    /**
     * Set the value of new.
     * Model is new - data hasn't been saved.
     *
     * @return static
     */
    protected function setNew(bool $new)
    {
        $this->_new = $new;

        return $this;
    }

    /**
     * Get the value of new.
     * Model is new - data hasn't been saved.
     */
    public function isNew()
    {
        return $this->_new;
    }

    /**
     * Get the value of _autoLoadRelations.
     */
    public function getAutoLoadRelations()
    {
        return $this->_autoLoadRelations;
    }

    /**
     * Set the value of _autoLoadRelations.
     *
     * @return static
     */
    public function setAutoLoadRelations(bool $autoLoadRelations)
    {
        $this->_autoLoadRelations = $autoLoadRelations;

        return $this;
    }

    /**
     * Set requested relations.
     *
     * @param array<string>|array<string,callable> $requestedRelations List of relation names or Relation name as key with callback as value.
     *                                                                 Only these relation names will loaded if auto load relations not enabled.
     *
     * @return static
     */
    public function setRequestedRelations(array $requestedRelations)
    {
        $this->_requestedRelations = $requestedRelations;

        return $this;
    }

    /**
     * Set loaded relations names.
     *
     * @param array<string> $loadedRelations Loaded relations names.
     *
     * @return static
     */
    public function setLoadedRelations(array $loadedRelations)
    {
        $this->_loadedRelations = $loadedRelations;

        return $this;
    }

    /**
     * Add loaded relations name.
     *
     * @param string $loadedRelation Loaded relations name.
     *
     * @return static
     */
    public function addLoadedRelation(string $loadedRelation)
    {
        $this->_loadedRelations[] = $loadedRelation;

        return $this;
    }

    /**
     * Model table name. e.g db table, collection name.
     *
     * @return string
     */
    abstract public function getTableName(): string;

    /**
     * Model key name. e.g table primary key, unique index.
     *
     * @return string|null
     */
    abstract public function getKeyName(): ?string;

    /**
     * Model relations.
     *
     * @return \Armie\Data\PDO\Relation<static,self>[]
     */
    abstract public function getRelations(): array;

    /**
     * Model fields.
     *
     * @return \Armie\Data\PDO\Field[]
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
        $fieldNames = !empty($fields) ? array_map(fn ($field) => strval($field), $fields) : array_keys(parent::fields(false));
        $relationNames = $this->getRelationNames();

        return array_diff($fieldNames, $relationNames);
    }

    /**
     * Model created date param name. e.g created_at, createdAt.
     *
     * @return string
     */
    public function getCreatedDateName(): ?string
    {
        return null;
    }

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt.
     *
     * @return string
     */
    public function getUpdatedDateName(): ?string
    {
        return null;
    }

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt.
     *
     * @return string
     */
    public function getSoftDeleteDateName(): ?string
    {
        return null;
    }

    /**
     * Check if model was soft deleted.
     *
     * @return bool
     */
    public function isTrashed(): bool
    {
        return !empty($this->getSoftDeleteDateName()) && isset($this->{$this->getSoftDeleteDateName()});
    }

    /**
     * @inheritDoc
     */
    public function count(string|null $query = null, $params = []): int
    {
        $query = $query ? $this->getDatabase()->applyCount($query) : sprintf('SELECT COUNT(*) FROM %s', $this->getTableName());
        if ($query) {
            $stmt = $this->getDatabase()->prepare($query);
            if ($stmt && $stmt->execute($params) && ($result = $stmt->fetchColumn())) {
                return intval($result);
            }
        }

        return 0;
    }

    /**
     * @inheritDoc
     *
     * @return ?static
     */
    public function find($id, $conditions = [], $params = [], $columns = []): ?static
    {
        if (!empty($this->getSoftDeleteDateName())) {
            return $this->findWhere(array_merge($conditions, [
                $this->getKeyName() => ':id',
                sprintf('ISNULL(%s)', $this->getSoftDeleteDateName()),
            ]), array_merge($params, [
                ':id' => $id,
            ]), $columns);
        } else {
            return $this->findWhere(array_merge($conditions, [
                $this->getKeyName() => ':id',
            ]), array_merge($params, [
                ':id' => $id,
            ]), $columns);
        }
    }

    /**
     * @inheritDoc
     *
     * @return ?static
     */
    public function findTrashed($id, $conditions = [], $params = [], $columns = []): ?static
    {
        return $this->findWhere(array_merge($conditions, [
            $this->getKeyName() => ':id',
        ]), array_merge($params, [
            ':id' => $id,
        ]), $columns);
    }

    /**
     * @inheritDoc
     *
     * @return ?static
     */
    public function findWhere($conditions = [], $params = [], $columns = []): ?static
    {
        if (empty($columns)) {
            $columns = ['*'];
        }

        $colsPlaceHolders = $this->parseColumns($columns);
        $condPlaceHolders = $this->parseConditions($conditions);

        $stmt = $this->_db->prepare(sprintf(
            'SELECT %s FROM %s %s LIMIT 1',
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? 'WHERE ' . $condPlaceHolders : ''
        ));

        // Dispatch event
        dispatch(static::EVENT_BEFORE_QUERY, ['query' => $stmt->queryString, 'params' => $params]);

        if ($stmt && $stmt->execute($params)) {
            // Dispatch event
            dispatch(static::EVENT_AFTER_QUERY, ['query' => $stmt->queryString, 'params' => $params]);

            if (($result = $stmt->fetch(Connection::FETCH_ASSOC)) !== false) {
                return $this->clone()
                    ->fastLoad($result)
                    ->setNew(false)
                    ->processAutoLoadRelations()
                    ->select($this->mergeColumnsAndRelations($columns));
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     *
     * @return static[]
     */
    public function all($conditions = [], $params = [], $columns = [], int $limit = 0, int $page = 0): array
    {
        if (!empty($this->getSoftDeleteDateName())) {
            return $this->allTrashed(array_merge($conditions, [
                sprintf('ISNULL(%s)', $this->getSoftDeleteDateName()),
            ]), $params, $columns, $limit);
        } else {
            return $this->allTrashed($conditions, $params, $columns, $limit, $page);
        }
    }

    /**
     * @inheritDoc
     *
     * @return static[]
     */
    public function allTrashed($conditions = [], $params = [], $columns = [], int $limit = 0, int $page = 0): array
    {
        if (empty($columns)) {
            $columns = ['*'];
        }

        $colsPlaceHolders = $this->parseColumns($columns);
        $condPlaceHolders = $this->parseConditions($conditions);
        $limit = $limit > 0 ? $limit : $this->getPerPage();
        $offset = $this->_db->getOffset($page, $limit);

        $stmt = $this->_db->prepare(sprintf(
            'SELECT %s FROM %s %s %s',
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? "WHERE $condPlaceHolders" : '',
            $limit >= 0 ? "LIMIT $offset, $limit" : ''
        ));

        // Dispatch event
        dispatch(static::EVENT_BEFORE_QUERY, ['query' => $stmt->queryString, 'params' => $params]);

        if ($stmt && $stmt->execute($params)) {
            // Dispatch event
            dispatch(static::EVENT_AFTER_QUERY, ['query' => $stmt->queryString, 'params' => $params]);

            $results = [];

            while (($result = $stmt->fetch(Connection::FETCH_ASSOC)) !== false) {
                $results[] = $this->clone()
                    ->fastLoad($result)
                    ->setNew(false)
                    ->select($this->mergeColumnsAndRelations($columns));
            }

            return $this->processEagerLoadRelations($results);
        }

        return [];
    }

    /**
     * @inheritDoc
     *
     * @return Generator<int,static>
     */
    public function itterate($conditions = [], $params = [], $columns = [], int $limit = 0, int $page = 0): Generator
    {
        if (empty($columns)) {
            $columns = ['*'];
        }

        $colsPlaceHolders = $this->parseColumns($columns);
        $condPlaceHolders = $this->parseConditions($conditions);
        $limit = $limit > 0 ? $limit : $this->getPerPage();
        $offset = $this->_db->getOffset($page, $limit);

        $stmt = $this->_db->prepare(sprintf(
            'SELECT %s FROM %s %s %s',
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? "WHERE $condPlaceHolders" : '',
            $limit >= 0 ? "LIMIT $offset, $limit" : ''
        ));

        // Dispatch event
        dispatch(static::EVENT_BEFORE_QUERY, ['query' => $stmt->queryString, 'params' => $params]);

        if ($stmt && $stmt->execute($params)) {
            // Dispatch event
            dispatch(static::EVENT_AFTER_QUERY, ['query' => $stmt->queryString, 'params' => $params]);

            while (($result = $stmt->fetch(Connection::FETCH_ASSOC)) !== false) {
                yield $this->clone()
                    ->fastLoad($result)
                    ->setNew(false)
                    ->processAutoLoadRelations()
                    ->select($this->mergeColumnsAndRelations($columns));
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function delete($force = false): bool
    {
        // Soft delele
        if (!$force && !empty($this->getSoftDeleteDateName())) {
            $this->{$this->getSoftDeleteDateName()} = strval(new StringableDateTime());

            return $this->save() !== false;
        }

        // Permanent delete
        else {
            // Dispatch event
            dispatch(static::EVENT_BEFORE_DELETE, $this->toArray());

            $stmt = $this->_db->prepare(sprintf(
                'DELETE FROM %s WHERE `%s` = ?',
                $this->getTableName(),
                $this->getKeyName()
            ));

            if ($stmt) {
                $stmt->execute([$this->{$this->getKeyName()}]);
                if ($stmt->rowCount() > 0) {
                    // Dispatch event
                    dispatch(static::EVENT_AFTER_DELETE);

                    return true;
                }
            }

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function restore(): bool
    {
        if ($this->getSoftDeleteDateName() && isset($this->{$this->getSoftDeleteDateName()})) {
            $this->{$this->getSoftDeleteDateName()} = null;

            return $this->save() !== false;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function save($trim = false, $relations = true): bool
    {
        // Create
        if ($this->_new || !isset($this->{$this->getKeyName()})) {
            // Add created & updated dates if not available
            if (!empty($this->getCreatedDateName())) {
                $this->{$this->getCreatedDateName()} = strval(new StringableDateTime());
            }
            if (!empty($this->getUpdatedDateName())) {
                $this->{$this->getUpdatedDateName()} = strval(new StringableDateTime());
            }

            $params = $this->select($this->getFieldNames())->toArray($trim);
            if (empty($params)) {
                return false;
            }

            // Dispatch event
            dispatch(static::EVENT_BEFORE_CREATE, $params);

            // Process query
            $placeHolderKeys = implode(',', array_map(fn ($key) => "`$key`", array_keys($params)));
            $placeHolderValues = implode(',', array_fill(0, count($params), '?'));
            $stmt = $this->_db->prepare(sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $this->getTableName(),
                $placeHolderKeys,
                $placeHolderValues
            ));
            if (!$stmt || !$stmt->execute(array_values($params))) {
                return false;
            }

            // Update key for Auto Increment
            if (!isset($this->{$this->getKeyName()}) && !empty($id = $this->_db->lastInsertId())) {
                $this->{$this->getKeyName()} = $id;
            }

            // Dispatch event
            dispatch(static::EVENT_AFTER_CREATE, $params);

            // Notify record exists
            $this->_new = false;

            // Save relations if available
            if ($relations) {
                $this->saveRelations();
            }
        }

        // Update
        elseif ($this->isDirty()) {
            // Add updated date if not available
            if (!empty($this->getUpdatedDateName())) {
                $this->{$this->getUpdatedDateName()} = strval(new StringableDateTime());
            }

            $params = $this->select($this->getFieldNames())->toArray($trim);
            if (empty($params)) {
                return false;
            }

            // Dispatch event
            dispatch(static::EVENT_BEFORE_UPDATE, $params);

            // Process query
            $placeHolder = implode(',', array_map(fn ($key) => "`$key` = ?", array_keys($params)));
            $stmt = $this->_db->prepare(sprintf(
                'UPDATE %s SET %s WHERE `%s` = ?',
                $this->getTableName(),
                $placeHolder,
                $this->getKeyName()
            ));
            if (!$stmt || !$stmt->execute([...array_values($params), $this->{$this->getKeyName()}])) {
                return false;
            }

            // Dispatch event
            dispatch(static::EVENT_AFTER_UPDATE, $params);

            // Notify record exists
            $this->_new = false;

            // Save relations if available
            if ($relations) {
                $this->saveRelations();
            }
        }

        return true;
    }

    /**
     * Save relations.
     *
     * @return bool
     */
    protected function saveRelations(): bool
    {
        $success = true;
        foreach ($this->getRelations() as $relation) {
            $data = $this->{$relation->getName()} ?? null;
            if (isset($data)) {
                if ($data instanceof static) {
                    $success = !$data->isDirty() || !$data->save() ? false : $success;
                } elseif (is_array($data)) {
                    $success = !$relation->save($data) ? false : $success;
                }
            }
        }

        return $success;
    }

    /**
     * Start database transaction
     *
     * @return void
     */
    public function startTransaction(): void
    {
        $this->_db->beginTransaction();
    }

    /**
     * End database transaction
     *
     * @param boolean $rollback
     * @return void
     */
    public function endTransaction(bool $rollback = false): void
    {
        if ($rollback) {
            $this->_db->rollBack();
        }
        $this->_db->commit();
    }

    /**
     * Perform database transaction. Auto rollback if unsuccessful.
     *
     * @param callable $callable Return FALSE if unsuccessful
     *
     * @return mixed result of $callable.
     */
    public function transaction(callable $callable)
    {
        $this->startTransaction();
        $result = null;
        try {
            $result = $callable();
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            $this->endTransaction($result === false);
        }

        return $result;
    }

    /**
     * Process eager loading of relations.
     *
     * @param static[] $items
     *
     * @return static[]
     */
    public function processEagerLoadRelations(array $items): array
    {
        return $this->_autoLoadRelations || !empty($this->_requestedRelations) ? $this->eagerLoadRelations($items) : $items;
    }

    /**
     * Process auto loading of relations.
     *
     * @return static
     */
    public function processAutoLoadRelations(): static
    {
        return $this->_autoLoadRelations || !empty($this->_requestedRelations) ? $this->loadRelations() : $this;
    }

    /**
     * Eager load relations.
     *
     * @param static[] $items
     *
     * @return static[]
     */
    public function eagerLoadRelations(array $items): array
    {
        $requestedRelations = array_is_list($this->_requestedRelations) ? $this->_requestedRelations : array_keys($this->_requestedRelations);
        foreach ($this->getRelations() as &$relation) {
            if (empty($this->_requestedRelations) || in_array($relation->getName(), $requestedRelations)) {
                // Trigger callback if available
                $callback = $this->_requestedRelations[$relation->getName()] ?? null;
                if ($callback && is_callable($callback)) {
                    $callback($relation);
                }
                $items = $relation->load($items);
            }
        }

        return $items;
    }

    /**
     * Load relations.
     *
     * @return static
     */
    public function loadRelations(): static
    {
        $requestedRelations = array_is_list($this->_requestedRelations) ? $this->_requestedRelations : array_keys($this->_requestedRelations);
        foreach ($this->getRelations() as &$relation) {
            if (empty($this->_requestedRelations) || in_array($relation->getName(), $requestedRelations)) {
                // Trigger callback if available
                $callback = $this->_requestedRelations[$relation->getName()] ?? null;
                if ($callback && is_callable($callback)) {
                    $callback($relation);
                }
                $this->{$relation->getName()} = $relation->get();
                $this->_loadedRelations[] = $relation->getName();
            }
        }

        return $this;
    }

    /**
     * Load single relation by name.
     *
     * @param string   $name
     * @param callable $callback Anonymous function with `Relation::class` as parameter
     *
     * @return static
     */
    public function loadRelation(string $name, callable $callback = null): static
    {
        foreach ($this->getRelations() as &$relation) {
            if (strtolower($name) === strtolower($relation->getName())) {
                // Trigger callback if available
                if ($callback) {
                    $callback($relation);
                }

                $this->{$relation->getName()} = $relation->get();
                $this->_loadedRelations[] = $relation->getName();

                return $this;
            }
        }

        return $this;
    }

    /**
     * Merge columns with relation names.
     *
     * @param array $columns
     *
     * @return array
     */
    public function mergeColumnsAndRelations(array $columns): array
    {
        $columns = !empty($this->selected()) && !in_array('*', $this->selected()) ? $this->selected() : $columns;

        if ($this->_autoLoadRelations) {
            return array_unique([...(array_is_list($columns) ? $columns : array_keys($columns)), ...$this->getRelationNames()]);
        } elseif (!empty($this->_loadedRelations)) {
            return array_unique([...(array_is_list($columns) ? $columns : array_keys($columns)), ...$this->_loadedRelations]);
        }

        return array_is_list($columns) ? $columns : array_keys($columns);
    }

    /**
     * Parse query colomns.
     *
     * @param array $columns
     *
     * @return string
     */
    public function parseColumns(array $columns): string
    {
        $cols = [];
        $relationCols = [];
        $validCols = array_keys($this->fields());

        // If all columns not selected:
        // Always include relation columns
        // Always exclude relation names from valid columns
        if (!in_array('*', $columns)) {
            foreach ($this->getRelations() as $relation) {
                $validCols = array_diff($validCols, [$relation->getName()]);
                $relationCols = array_keys($relation->getReferences());
                $columns = array_merge($columns, $relationCols); // Add relationship columns
                $columns = array_intersect($columns, $validCols); // Remove columns not in valid columns
            }
        }

        foreach ($columns as $key => $col) {
            if (!str_starts_with($col, '-')) {
                if ($col === '*') {
                    if (!in_array($col, $cols)) {
                        $cols = [$col];
                        break;
                    }
                } else {
                    $cols[] = is_numeric($key) ? "`$col`" : sprintf('`%s` AS %s', $this->cleanCond($key), $this->cleanCond($col));
                }
            }
        }

        return  implode(',', $cols);
    }

    /**
     * Parse query conditions.
     *
     * @param array $conditions
     *
     * @return string
     */
    public function parseConditions(array $conditions): string
    {
        $result = null;

        $parseCondtionalArray = function ($result, $key, $cond) {
            $key = strtoupper($key);

            return  $result ?
                $result . sprintf(' %s %s', in_array($key, ['AND', 'OR']) ? $key : 'AND', $this->parseConditions($cond)) :
                sprintf('%s %s', $key == 'NOT' ? $key : '', $this->parseConditions($cond));
        };
        $parseArrayList = function ($result, $key, $cond) {
            return $result ?
                $result . ' AND ' . sprintf('`%s` IN (%s)', $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond))) :
                sprintf('`%s` IN (%s)', $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond)));
        };
        $parseArray = function ($result, $cond) {
            return  $result ?
                $result . ' AND ' . sprintf('%s', $this->parseConditions($cond)) :
                sprintf('%s', $this->parseConditions($cond));
        };
        $parseString = function ($result, $cond) {
            return $result ?
                $result . ' AND ' . sprintf('(%s)', $this->cleanCond($cond)) :
                sprintf('(%s)', $this->cleanCond($cond));
        };
        $parseKeyedString = function ($result, $key, $cond) {
            // Key is a conditional operator
            if (in_array(strtoupper($key), ['AND', 'OR'])) {
                return $result ?
                    $result . sprintf(' %s (%s)', strtoupper($key), $this->cleanCond($cond)) :
                    sprintf('(%s)', $this->cleanCond($cond));
            }
            // Key is a conditional (NOT) operator
            elseif (strtoupper($key) == 'NOT') {
                return $result ?
                    $result . sprintf('(%s)', $this->cleanCond($cond)) :
                    sprintf('%s (%s)', $key, $this->cleanCond($cond));
            }
            // Key is a parameter
            else {
                return $result ?
                    $result . ' AND ' . sprintf('`%s` = %s', $key, $this->escapeCond($cond)) :
                    sprintf('`%s` = %s', $key, $this->escapeCond($cond));
            }
        };

        if (!empty($conditions)) {
            // List type ([a,b,c])
            if (array_is_list($conditions)) {
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
                        if (array_is_list($cond)) {
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
     * Add quotes to condition if needed.
     *
     * @param string $cond
     *
     * @return string
     */
    private function escapeCond(string $cond): string
    {
        return $cond == '?' || str_starts_with($cond, ':') ? $cond : "'$cond'";
    }

    /**
     * Remove undesirable values from condition.
     *
     * @param string $cond
     *
     * @return string
     */
    private function cleanCond(string $cond): string
    {
        return trim($cond == '?' ? $cond : preg_replace("/\/|\/\*|\*\/|where|join|from/im", '', $cond));
    }


    #### Statics #####


    /**
     * Create model instance with custom pagination limit
     *
     * @param int $limit
     *
     * @return static
     */
    public static function withLimit(int $limit): static
    {
        return (new static())->setPerPage($limit);
    }

    /**
     * Create model instance with relations to be loaded.
     *
     * @param array<string>|array<string,callable> $relations List of relation names or Relation name as key with callback as value.
     *
     * @return static
     */
    public static function withRelations(array $relations): static
    {
        return (new static())->setRequestedRelations($relations);
    }

    /**
     * Create model instance with support for auto loaded relations.
     *
     * @return static
     */
    public static function withAutoRelations(): static
    {
        return (new static())->setAutoLoadRelations(true);
    }

    /**
     * Create model instance without support for auto loaded relations.
     *
     * @return static
     */
    public static function withoutAutoRelations(): static
    {
        return (new static())->setAutoLoadRelations(false);
    }

    /**
     * Create record.
     *
     * @param array $data
     *
     * @return static|null
     */
    public static function create(array $data): ?static
    {
        $model = new static();
        $model->load($data);
        if ($model->save()) {
            return $model;
        }

        return null;
    }

    /**
     * Update record.
     *
     * @param string|int $id
     * @param array      $data
     *
     * @return static|null
     */
    public static function update(string|int $id, array $data): ?static
    {
        $model = static::findById($id);
        if ($model && $model->fastLoad($data)->save()) {
            return $model;
        }

        return null;
    }

    /**
     * Find model for id. Without trashed (deleted) models.
     *
     * @param string|int $id
     * @param array      $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array      $params     Query Params. e.g SQL query params `[$id]` or [':id' => $id]
     * @param array      $columns    Select Colomn names.
     *
     * @return static|null
     */
    public static function findById(string|int $id, array $conditions = [], array $params = [], array $columns = []): ?static
    {
        return (new static())->find($id, $conditions, $params, $columns);
    }

    /**
     * Find model for id. With trashed (deleted) models.
     *
     * @param string|int $id
     * @param array      $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array      $params     Query Params. e.g SQL query params `[$id]` or [':id' => $id]
     * @param array      $columns    Select Colomn names.
     *
     * @return static|null
     */
    public static function findTrashedById(string|int $id, array $conditions = [], array $params = [], array $columns = []): ?static
    {
        return (new static())->findTrashed($id, $conditions, $params, $columns);
    }

    /**
     * Get list of model. Without trashed (deleted) models.
     *
     * @param array    $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array    $params     Query Params. e.g SQL query params `[$id]` or [':id' => $id]
     * @param array    $columns    Select Colomn names.
     * @param int|null $limit      Query limit
     *
     * @return static[]
     */
    public static function getAll(array $conditions = [], array $params = [], array $columns = [], int|null $limit = null): array
    {
        $model = (new static());

        return $model->setAutoLoadRelations(false)->all($conditions, $params, $columns, $limit ?? 0);
    }

    /**
     * Get list of model. With trashed (deleted) models.
     *
     * @param array    $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array    $params     Query Params. e.g SQL query params `[$id]` or [':id' => $id]
     * @param array    $columns    Select Colomn names.
     * @param int|null $limit      Query limit
     *
     * @return static[]
     */
    public static function getAllTrashed(array $conditions = [], array $params = [], array $columns = [], int|null $limit = null): array
    {
        $model = (new static());

        return $model->setAutoLoadRelations(false)->allTrashed($conditions, $params, $columns, $limit ?? 0);
    }

    /**
     * Itterate upon list of model. With trashed (deleted) models.
     *
     * @param array    $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array    $params     Query Params. e.g SQL query params `[$id]` or [':id' => $id]
     * @param array    $columns    Select Colomn names.
     * @param int|null $limit      Query limit
     *
     * @return Generator<int,static>
     */
    public static function itterateAll(array $conditions = [], array $params = [], array $columns = [], int|null $limit = null): Generator
    {
        $model = (new static());

        return $model->setAutoLoadRelations(false)->itterate($conditions, $params, $columns, $limit ?? 0);
    }


    #### Clones #####

    /**
     * Clone model.
     *
     * @return static
     */
    public function clone()
    {
        $model = (clone $this);

        return $model;
    }

    #### Override #####

    /**
     * Is Dirty - Update has been made.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return !$this->_new && parent::isDirty();
    }

    /**
     * @inheritDoc
     */
    public function fields($all = false, $trim = false): array
    {
        if (!empty($fields = $this->getFields())) {
            $attrs = [];
            if ($this->_autoLoadRelations) {
                $fields = array_merge($fields, $this->getRelations());
            } elseif (!empty($this->_loadedRelations)) {
                $fields = array_merge($fields, array_filter($this->getRelations(), fn ($rel) => in_array(strval($rel), $this->_loadedRelations)));
            }
            foreach ($fields as $field) {
                $attrs[$field->getName()] = $field->getType()->value;
            }

            return $attrs;
        }

        return parent::fields($all, $trim);
    }
}
