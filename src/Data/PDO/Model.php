<?php

namespace Armie\Data\PDO;

use Armie\Data\DataObject;
use Armie\Helpers\StringableDateTime;
use Armie\Interfaces\Data\ModelInterface;
use Generator;

use function Armie\Helpers\dispatch;
use function Armie\Helpers\log_debug;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
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
                '_new', '_perPage',
            ]
        );
    }

    public function __sleep(): array
    {
        return array_merge(
            parent::__sleep(),
            [
                '_autoLoadRelations', '_requestedRelations', '_loadedRelations',
                '_new', '_perPage',
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
        $this->_requestedRelations = array_filter($requestedRelations, fn ($key) => in_array($key, $this->getRelationNames()), ARRAY_FILTER_USE_KEY);

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
        return !empty($this->getSoftDeleteDateName()) && !empty($this->get($this->getSoftDeleteDateName()));
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
                    ->setIsDirty(false)
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
    public function all($conditions = [], $params = [], $columns = [], array $sort = [], int $limit = 0, int $page = 0): array
    {
        if (!empty($this->getSoftDeleteDateName())) {
            return $this->allTrashed(array_merge($conditions, [
                sprintf('ISNULL(%s)', $this->getSoftDeleteDateName()),
            ]), $params, $columns, $sort, $limit);
        } else {
            return $this->allTrashed($conditions, $params, $columns, $sort, $limit, $page);
        }
    }

    /**
     * @inheritDoc
     *
     * @return static[]
     */
    public function allTrashed($conditions = [], $params = [], $columns = [], array $sort = [], int $limit = 0, int $page = 0): array
    {
        if (empty($columns)) {
            $columns = ['*'];
        }

        $colsPlaceHolders = $this->parseColumns($columns);
        $condPlaceHolders = $this->parseConditions($conditions);
        $sortPlaceHolders = $this->parseSort($sort);
        $limit = $limit > 0 ? $limit : $this->getPerPage();
        $offset = $this->_db->getOffset($page, $limit);

        $stmt = $this->_db->prepare(sprintf(
            'SELECT %s FROM %s %s %s %s',
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? "WHERE $condPlaceHolders" : '',
            !empty($sortPlaceHolders) ? "ORDER BY $sortPlaceHolders" : '',
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
                    ->setIsDirty(false)
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
    public function itterate($conditions = [], $params = [], $columns = [], array $sort = [], int $limit = 0, int $page = 0): Generator
    {
        if (empty($columns)) {
            $columns = ['*'];
        }

        $colsPlaceHolders = $this->parseColumns($columns);
        $condPlaceHolders = $this->parseConditions($conditions);
        $sortPlaceHolders = $this->parseSort($sort);
        $limit = $limit > 0 ? $limit : $this->getPerPage();
        $offset = $this->_db->getOffset($page, $limit);

        $stmt = $this->_db->prepare(sprintf(
            'SELECT %s FROM %s %s %s %s',
            $colsPlaceHolders,
            $this->getTableName(),
            !empty($condPlaceHolders) ? "WHERE $condPlaceHolders" : '',
            !empty($sortPlaceHolders) ? "ORDER BY $sortPlaceHolders" : '',
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
                    ->setIsDirty(false)
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
            $this->set($this->getSoftDeleteDateName(), strval(new StringableDateTime()));

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
                $stmt->execute([$this->get($this->getKeyName())]);
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
        if ($this->getSoftDeleteDateName() && empty($this->get($this->getSoftDeleteDateName()))) {
            $this->set($this->getSoftDeleteDateName(), null);

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
        if ($this->_new || empty($this->get($this->getKeyName()))) {
            // Add created & updated dates if not available
            if (!empty($this->getCreatedDateName())) {
                $this->set($this->getCreatedDateName(), strval(new StringableDateTime()));
            }
            if (!empty($this->getUpdatedDateName())) {
                $this->set($this->getUpdatedDateName(), strval(new StringableDateTime()));
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
            if (empty($this->get($this->getKeyName())) && !empty($id = $this->_db->lastInsertId())) {
                $this->set($this->getKeyName(), $id);
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
                $this->set($this->getUpdatedDateName(), strval(new StringableDateTime()));
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
            if (!$stmt || !$stmt->execute([...array_values($params), $this->get($this->getKeyName())])) {
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
            $data = $this->get($relation->getName()) ?? null;
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
     * End database transaction.
     *
     * @param bool $rollback
     *
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
     * @param callable():T $callable Return FALSE if unsuccessful
     *
     * @return T result of $callable.
     * @template T
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
    protected function processEagerLoadRelations(array $items): array
    {
        return $this->_autoLoadRelations || !empty($this->_requestedRelations) ? $this->eagerLoadRelations($items) : $items;
    }

    /**
     * Process auto loading of relations.
     *
     * @return static
     */
    protected function processAutoLoadRelations(): static
    {
        if ($this->isNew()) {
            return $this;
        }

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
            if (empty($requestedRelations) || in_array($relation->getName(), $requestedRelations)) {
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
        if ($this->isNew()) {
            return $this->setAutoLoadRelations(true);
        }

        $requestedRelations = array_is_list($this->_requestedRelations) ? $this->_requestedRelations : array_keys($this->_requestedRelations);
        foreach ($this->getRelations() as &$relation) {
            if (empty($requestedRelations) || in_array($relation->getName(), $requestedRelations)) {
                // Trigger callback if available
                $callback = $this->_requestedRelations[$relation->getName()] ?? null;
                if ($callback && is_callable($callback)) {
                    $callback($relation);
                }

                $this->set($relation->getName(), $relation->get());
                $this->_loadedRelations[] = $relation->getName();
                $this->select([...$this->_selected, $relation->getName()]);
            }
        }

        return $this;
    }

    /**
     * Load single relation by name.
     *
     * @param string   $name     Relation name to load. @see self::getRelations
     * @param callable $callback Use this to modify relation query. Anonymous function with instance of `Relation::class` as parameter
     *
     * @return static
     */
    public function loadRelation(string $name, callable $callback = null): static
    {
        if ($this->isNew()) {
            return $this->setAutoLoadRelations(true)->setRequestedRelations([...$this->_requestedRelations, $name => $callback]);
        }

        foreach ($this->getRelations() as &$relation) {
            if (strtolower($name) === strtolower($relation->getName())) {
                // Trigger callback if available
                if ($callback) {
                    $callback($relation);
                }

                $this->set($relation->getName(), $relation->get());
                $this->_loadedRelations[] = $relation->getName();
                $this->select([...$this->_selected, $relation->getName()]);

                return $this;
            }
        }

        return $this;
    }

    /**
     * Merge columns with loaded relation names.
     *
     * @param array $columns
     *
     * @return array
     */
    protected function mergeColumnsAndRelations(array $columns): array
    {
        $columns = !empty($this->selected()) && !in_array('*', $this->selected()) ? $this->selected() : $columns;

        return array_unique([...(array_is_list($columns) ? $columns : array_keys($columns)), ...$this->_loadedRelations]);
    }

    /**
     * Parse query columns.
     *
     * @param array $columns
     *
     * @return string
     */
    protected function parseColumns(array $columns): string
    {
        // If all columns selected
        if (in_array('*', $columns)) return "*";

        $parsedCols = [];
        $fieldNames = $this->getFieldNames();
        $relationNames = $this->getRelationNames();

        // Always include relation mapping column names
        foreach ($this->getRelations() as $relation) {
            $columns = array_merge($columns, array_keys($relation->getReferences()));
        }

        // Always exclude relation names from valid columns
        foreach (array_unique($columns) as $key => $col) {
            // Add relation columns to requested relations
            if (in_array($col, $relationNames)) $this->_requestedRelations[] = $col;
            // Only include field names in columns to be parsed. Exclude not negated fields (market to excludes)
            else if (is_string($col) && in_array($col, $fieldNames) && !str_starts_with($col, '-')) {
                $parsedCols[] = is_numeric($key) ? "`$col`" : sprintf('`%s` AS %s', $this->cleanCond($key), $this->cleanCond($col));
            }
        }

        return  implode(',', $parsedCols);
    }

    /**
     * Parse query conditions.
     *
     * @param array $conditions
     *
     * @return string
     */
    protected function parseConditions(array $conditions): string
    {
        $result = null;

        // Conditional array
        $parseCondtionalArray = function ($result, $key, $cond): string {
            $key = strtoupper($key);

            // Key is a conditional (teneray) operator
            if (in_array($key, ['=', '!=', '<>', '>', '<', '>=', '<='])) {
                $list = [];
                foreach ($cond as $col => $value) {
                    $list[] =  sprintf('(`%s` %s %s)', $col, $key, $this->escapeCond($value));
                }
                return $result ? $result . sprintf(' AND %s', implode(' AND ', $list)) :
                    sprintf('%s', implode(' AND ', $list));
            }
            // Key is a conditional query values
            elseif (in_array($key, ['AND', 'OR'])) {
                return $result ? $result . sprintf(' %s %s', $key, $this->parseConditions($cond)) :
                    $this->parseConditions($cond);
            }
            // Key is a conditional (NOT) operator
            elseif (strtoupper($key) == 'NOT') {
                return  $result ? $result . sprintf(' NOT %s', $this->parseConditions($cond)) :
                    sprintf('NOT %s', $this->parseConditions($cond));
            }
            return  $result ? $result . sprintf(' AND %s', $this->parseConditions($cond)) :
                $this->parseConditions($cond);
        };
        // Array list
        $parseArrayList = function ($result, $key, $cond): string {
            return $result ?
                $result . ' AND ' . sprintf('`%s` IN (%s)', $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond))) :
                sprintf('`%s` IN (%s)', $key, implode(',', array_map(fn ($c) => $this->escapeCond($c), $cond)));
        };
        // Array string
        $parseArray = function ($result, $cond): string {
            return  $result ?
                $result . ' AND ' . sprintf('%s', $this->parseConditions($cond)) :
                sprintf('%s', $this->parseConditions($cond));
        };
        // String
        $parseString = function ($result, $cond): string {
            return $result ?
                $result . ' AND ' . sprintf('(%s)', $this->cleanCond($cond)) :
                sprintf('(%s)', $this->cleanCond($cond));
        };
        // String with key
        $parseKeyedString = function ($result, $key, $cond): string {
            // Key is a conditional query values
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
                        // List type with sql tenary operators (['AND/OR/=/>/</>=/<=/!=' => [a=>1, b=>2, c=>3]])
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
     * Parse sort query.
     *
     * @param array $sort
     *
     * @return string
     */
    protected function parseSort(array $sort): string
    {
        $list = [];
        $fieldNames = $this->getFieldNames();

        foreach ($sort as $key => $value) {
            // Key not available - only value
            if (is_numeric($key)) {
                $list[] = $this->cleanCond($value);
            }
            // Key available
            else if (in_array($key, $fieldNames)) {
                $value = strtoupper($value);
                $list[] = sprintf('`%s` %s', $this->cleanCond($key), in_array($value, ['ASC', 'DESC']) ? $value : 'ASC');
            }
        }

        return implode(', ', $list);
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
        $cond  = trim($cond);

        return $cond == '?'
            || (str_starts_with($cond, '`') && str_ends_with($cond, '`'))
            || (str_starts_with($cond, '\'') && str_ends_with($cond, '\''))
            || (str_starts_with($cond, '"') && str_ends_with($cond, '"'))
            ? $cond
            : preg_replace([
                "/(\/\/)|\/\*|\*\/|(--)/im",
                Connection::SELECT_QUERY_REGX,
                Connection::INSERT_QUERY_REGX,
                Connection::UPDATE_QUERY_REGX,
                Connection::DELETE_QUERY_REGX,
                Connection::LIMIT_QUERY_REGX,
            ], '', $cond);
    }

    //### Statics #####

    /**
     * Create model instance with custom pagination limit.
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
     * Create model instance with relations
     *
     * @param array<string>|array<string,callable> $relations List of relation names or Relation name as key with callback as value.
     *
     * @return static
     */
    public static function withRelations(array $relations = []): static
    {
        return (new static())->setAutoLoadRelations(true)->setRequestedRelations($relations);
    }

    /**
     * Create model instance without relations.
     *
     * @return static
     */
    public static function withoutRelations(): static
    {
        return (new static())->setAutoLoadRelations(false)->setRequestedRelations([]);
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
     * @param array      $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array      $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
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
     * @param array      $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array      $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
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
     * @param array    $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array    $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
     * @param array    $columns    Select Colomn names.
     * @param array    $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int|null $limit      Query limit
     *
     * @return static[]
     */
    public static function getAll(array $conditions = [], array $params = [], array $columns = [], array $sort = [], int|null $limit = null): array
    {
        $model = (new static());

        return $model->setAutoLoadRelations(false)->all($conditions, $params, $columns, $sort, $limit ?? 0);
    }

    /**
     * Get list of model. With trashed (deleted) models.
     *
     * @param array    $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array    $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
     * @param array    $columns    Select Colomn names.
     * @param array    $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int|null $limit      Query limit
     *
     * @return static[]
     */
    public static function getAllTrashed(array $conditions = [], array $params = [], array $columns = [], array $sort = [], int|null $limit = null): array
    {
        $model = (new static());

        return $model->setAutoLoadRelations(false)->allTrashed($conditions, $params, $columns, $sort, $limit ?? 0);
    }

    /**
     * Itterate upon list of model. With trashed (deleted) models.
     *
     * @param array    $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array    $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
     * @param array    $columns    Select Colomn names.
     * @param array    $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int|null $limit      Query limit
     *
     * @return Generator<int,static>
     */
    public static function itterateAll(array $conditions = [], array $params = [], array $columns = [], array $sort = [], int|null $limit = null): Generator
    {
        $model = (new static());

        return $model->setAutoLoadRelations(false)->itterate($conditions, $params, $columns, $sort, $limit ?? 0);
    }

    //### Clones #####

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

    //### Override #####

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
        $props = parent::fields($all, $trim);

        // Get model fields
        $fields = $this->getFields();

        // Get model relations fields
        if ($this->_autoLoadRelations) {
            $fields = array_merge($fields, $this->getRelations());
        } elseif (!empty($this->_loadedRelations)) {
            $fields = array_merge($fields, array_filter($this->getRelations(), fn ($rel) => in_array(strval($rel), $this->_loadedRelations)));
        }

        // Merge fields with props
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $props[$field->getName()] = $field->getType()->value;
            }
        }

        return $props;
    }
}
