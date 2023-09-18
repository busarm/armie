<?php

namespace Armie\Data\PDO;

use Armie\Interfaces\Data\RelationInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @template TCurrent
 * @template TReference
 */
abstract class Relation extends Field implements RelationInterface
{
    protected array $conditions = [];
    protected array $params = [];
    protected array $columns = ['*'];
    protected int $limit = -1;

    /**
     * Get relation references.
     *
     * @return array
     */
    abstract public function getReferences(): array;

    /**
     * Get relation reference model.
     *
     * @return Model&TReference
     */
    abstract public function getReferenceModel(): Model;

    /**
     * Get relation current model.
     *
     * @return Model&TCurrent
     */
    abstract public function getCurrentModel(): Model;

    /**
     * Get relation data.
     *
     * @return array<Model&TReference>|(Model&TReference)|null
     */
    abstract public function get(): array|Model|null;

    /**
     * Load relation data for list of items.
     *
     * @param array<Model&TCurrent> $items
     *
     * @return array<Model&TCurrent> $items with loaded relations
     */
    abstract public function load(array $items): array;

    /**
     * Save relation data.
     *
     * @param array $data
     *
     * @return bool
     */
    abstract public function save(array $data): bool;

    /**
     * Get the value of conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Set the value of conditions.
     *
     * @return self
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Get the value of params.
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the value of params. e.g SQL query bind params
     *
     * @return self
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get the value of columns.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set the value of columns. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     *
     * @return self
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get the value of limit.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set the value of limit.
     *
     * @return self
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }
}
