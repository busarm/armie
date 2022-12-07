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
class OneToOne extends Relation
{
    /**
     * @param string $name Relation attribute name in Current Model
     * @param Model $model Current Model 
     * @param Model $toModel Related Model
     * @param array $references e.g. `['modelKey1' => 'toModelKey1', 'modelKey2' => 'toModelKey2']`
     * @return void
     */
    public function __construct(
        private string $name,
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
     * @return Model|null
     */
    public function get(array $conditions = [], array $params = [], array $columns = ['*']): ?Model
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
            return $this->toModel->setAutoLoadRelations(false)->findWhere(
                array_merge($referenceConditions, $conditions),
                array_merge($referenceParams, $params),
                $columns
            );
        }
        return null;
    }

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
    public function load(array $items, array $conditions = [], array $params = [], array $columns = ['*']): array
    {
        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->references as $modelRef => $toModelRef) {
            $refs = array_map(fn ($item) => $item->{$modelRef}, $items);
            if (!empty($refs)) {
                $referenceConditions[] = sprintf("$toModelRef IN (%s)", implode(',', array_fill(0, count($refs), '?')));
                $referenceParams = array_merge($referenceParams, $refs);
            }
        }

        if (count($referenceConditions) && count($referenceConditions)) {

            // Get relation results for all items
            $results = $this->toModel->setAutoLoadRelations(false)->all(
                array_merge($referenceConditions, $conditions),
                array_merge($referenceParams, $params),
                $columns
            );

            // Group result for references
            $resultsMap = [];
            foreach ($results as $result) {
                $key = implode('-', array_map(fn ($ref) => $result->{$ref}, array_values($this->references)));
                $resultsMap[$key] = $result;  // Single item (1:1)
            }

            // Map relation for each item
            foreach ($items as &$item) {
                $key = implode('-', array_map(fn ($ref) => $item->{$ref}, array_keys($this->references)));
                $item->{$this->getName()} = $resultsMap[$key] ?? null;
                $item->addLoadedRelation($this->getName());
            }

            return $items;
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

    /**
     * Get relation current model
     * @return Model
     */
    public function getCurrentModel(): Model
    {
        return $this->model;
    }
}
