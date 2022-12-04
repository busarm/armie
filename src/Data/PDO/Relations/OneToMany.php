<?php

namespace Busarm\PhpMini\Data\PDO\Relations;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Data\PDO\Relation;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class OneToMany extends Relation
{
    /**
     * @param string $name Relation attribute name in Current Model
     * @param Model $model Current Model 
     * @param Model $toModel Related Model
     * @param array $references e.g. `['modelKey1' => 'toModelKey1', 'modelKey2' => 'toModelKey2']`
     * @return void
     */
    public function __construct(
        string $name,
        private Model $model,
        private Model $toModel,
        private array $references
    ) {
        parent::__construct($name, $toModel);
    }

    /**
     * Get relation data
     * 
     * @param array $conditions
     * @param array $params
     * @param array $columns
     * @return Model[]
     */
    public function get(array $conditions = [], array $params = [], array $columns = ['*']): array
    {
        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->references as $modelRef => $toModelRef) {
            if (isset($this->model->{$modelRef})) {
                $referenceConditions[] = "$toModelRef = :$toModelRef";
                $referenceParams[":$toModelRef"] = $this->model->{$modelRef};
            }
        }

        if (count($referenceConditions) && count($referenceConditions)) {
            return $this->toModel->setAutoLoadRelations(false)->all(
                array_merge($referenceConditions, $conditions),
                array_merge($referenceParams, $params),
                $columns
            );
        }
        return [];
    }

    /**
     * Get relation references
     * @return array
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * Get relation reference model
     * @return Model
     */
    public function getReferenceModel(): Model
    {
        return $this->toModel;
    }
}
