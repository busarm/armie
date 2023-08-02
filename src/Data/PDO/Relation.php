<?php

namespace Busarm\PhpMini\Data\PDO;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Interfaces\Data\RelationInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class Relation extends Field implements RelationInterface
{
    protected array $conditions = [];
    protected array $params = [];
    protected array $columns = ['*'];
    protected int $limit = -1;

    /**
     * Get relation references
     * @return array
     */
    abstract public function getReferences(): array;

    /**
     * Get relation reference model
     * @return Model
     */
    abstract public function getReferenceModel(): Model;

    /**
     * Get relation current model
     * @return Model
     */
    abstract public function getCurrentModel(): Model;

    /**
     * Get relation data
     * 
     * @return Model[]|Model|null
     */
    abstract public function get(): array|Model|null;

    /**
     * Load relation data for list of items
     * 
     * @param Model[] $items
     * @return Model[] $items with loaded relations
     */
    abstract public function load(array $items): array;

    /**
     * Save relation data
     * 
     * @param array $data
     * @return bool
     */
    abstract public function save(array $data): bool;

    /**
     * Get the value of conditions
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Set the value of conditions
     *
     * @return  self
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Get the value of params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the value of params
     *
     * @return  self
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get the value of columns
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Set the value of columns
     *
     * @return  self
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get the value of limit
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set the value of limit
     *
     * @return  self
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }
}
