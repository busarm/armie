<?php

namespace Busarm\PhpMini\Data\PDO;

use Busarm\PhpMini\Data\PDO\Model;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class Relation extends Field
{
    /**
     * @param string $name Relation name
     */
    public function __construct(private string $name, private Model $referenceModel)
    {
        parent::__construct($name, get_class($referenceModel));
    }

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
     * @param array $conditions
     * @param array $params
     * @param array $columns
     * @return Model[]|Model|null
     */
    abstract public function get(array $conditions = [], array $params = [], array $columns = ['*']): array|Model|null;

    /**
     * Load relation data for list of items
     * 
     * @param Model[] $items
     * @param array $conditions
     * @param array $conditions
     * @param array $params
     * @param array $columns
     * @return Model[] $items with loaded relations
     */
    abstract public function load(array $items, array $conditions = [], array $params = [], array $columns = ['*']): array;
}
