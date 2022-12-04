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
     * @param Model $pivotModel Pivot Model
     * @param array $references e.g. `['modelKey1' => 'pivotModelKey1', 'modelKey2' => 'pivotModelKey2']`
     * @return void
     */
    public function __construct(
        string $name,
        private Model $model,
        private Model $pivotModel,
        private array $references
    ) {
        parent::__construct($name, $pivotModel);
    }

    /**
     * Validate pivot model relations. 
     * Ensure that pivot only contains One to One Relations to avoid infinite loops
     * @return bool
     */
    public function validatePivotRelations(): bool
    {
        foreach ($this->pivotModel->getRelations() as $relations) {
            if (!($relations instanceof OneToOne)) {
                return false;
            }
        }
        return true;
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
        foreach ($this->references as $modelRef => $pivotModelRef) {
            if (isset($this->model->{$modelRef})) {
                $referenceConditions[] = "$pivotModelRef = :$pivotModelRef";
                $referenceParams[":$pivotModelRef"] = $this->model->{$modelRef};
            }
        }

        if (count($referenceConditions) && count($referenceConditions)) {
            return $this->pivotModel->setAutoLoadRelations($this->validatePivotRelations())->all(
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
        return $this->pivotModel;
    }
}
