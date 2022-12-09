<?php

namespace Busarm\PhpMini\Data\PDO\Relations;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Data\PDO\Reference;
use Busarm\PhpMini\Data\PDO\Relation;
use Busarm\PhpMini\Enums\DataType;

use function Busarm\PhpMini\Helpers\is_list;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ManyToMany extends Relation
{
    /**
     * @param string $name Relation attribute name in Current Model
     * @param Model $model Current Model 
     * @param Reference $pivotReference Pivot Relation Reference
     * @param Reference $itemReference Item Relation Reference
     * @return void
     */
    public function __construct(
        private string $name,
        private Model $model,
        private Reference $pivotReference,
        private Reference $itemReference
    ) {
        parent::__construct($name, DataType::ARRAY);
    }

    /**
     * Get relations in pivot model to be loaded
     * Only load relations linked to related model (self::getItemModel)
     * Ensure that pivot only contains One to One Relations to avoid infinite loops
     * 
     * @return string[]
     */
    private function getLoadablePivotRelationNames(): array
    {
        $list = [];
        foreach ($this->getReferenceModel()->getRelations() as $relation) {
            if (($relation instanceof OneToOne) && get_class($this->getItemModel()) === get_class($relation->getReferenceModel())) {
                $list[] = $relation->getName();
            }
        }
        return $list;
    }

    /**
     * Get relation data
     * 
     * @return Model[]
     */
    public function get(array $conditions = []): array
    {
        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->getReferences() as $modelRef => $pivotModelRef) {
            if (isset($this->model->{$modelRef})) {
                $referenceConditions[] = "`$pivotModelRef` = :$pivotModelRef";
                $referenceParams[":$pivotModelRef"] = $this->model->{$modelRef};
            }
        }

        if (count($referenceConditions) && count($referenceParams)) {
            return $this->getReferenceModel()
                ->withRelations($this->getLoadablePivotRelationNames())
                ->setAutoLoadRelations(false)
                ->setPerPage($this->limit)
                ->all(
                    array_merge($referenceConditions, $this->conditions),
                    array_merge($referenceParams, $this->params),
                    $this->columns
                );
        }
        return [];
    }

    /**
     * Load relation data for list of items
     * 
     * @param Model[] $items
     * @return Model[] $items with loaded relations
     */
    public function load(array $items): array
    {
        if (empty($items)) return [];

        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->getReferences() as $modelRef => $pivotModelRef) {
            $refs = array_map(fn ($item) => $item->{$modelRef}, $items);
            $referenceConditions[] = sprintf("`$pivotModelRef` IN (%s)", implode(',', array_fill(0, count($refs), '?')));
            $referenceParams = array_merge($referenceParams, $refs);
        }

        if (count($referenceConditions) && count($referenceConditions)) {

            // Get relation results for all items
            $results = $this->getReferenceModel()
                ->withRelations($this->getLoadablePivotRelationNames())
                ->setAutoLoadRelations(false)
                ->setPerPage($this->limit * count($items))
                ->all(
                    array_merge($referenceConditions, $this->conditions),
                    array_merge($referenceParams, $this->params),
                    $this->columns
                );

            // Group result for references
            $resultsMap = [];
            foreach ($results as $result) {
                $key = implode('-', array_map(fn ($ref) => $result->{$ref}, array_values($this->getReferences())));
                $resultsMap[$key][] = $result; // Multiple items (m:m)
            }

            // Map relation for each item
            foreach ($items as &$item) {
                $key = implode('-', array_map(fn ($ref) => $item->{$ref}, array_keys($this->getReferences())));
                $item->{$this->getName()} = $resultsMap[$key] ?? [];
                $item->addLoadedRelation($this->getName());
            }

            return $items;
        }
        return [];
    }

    /**
     * Save relation data
     * 
     * @param array|array<array> $data Singe array item or 2D array multiple items
     * @return bool
     */
    public function save(array $data): bool
    {
        if (empty($data)) return false;

        // Is multiple values
        if (is_list($data)) {
            return $this->getReferenceModel()->transaction(function () use ($data) {
                $done = true;
                foreach ($data as $item) {
                    if (is_array($item)) {
                        if (!$this->save($item)) $done = false;
                    }
                }
                return $done;
            });
        }

        $itemModel = $this->getItemModel()->clone();
        $referenceModel = $this->getReferenceModel()->clone();

        // Save related model
        $itemModel->load($data);
        if ($itemModel->save()) {

            $pivotData = [];

            $reFieldNames = $referenceModel->getFieldNames();

            // Load reference model keys for current model
            foreach ($this->getReferences() as $modelRef => $pivotModelRef) {
                if (isset($this->getCurrentModel()->{$modelRef}) && in_array($pivotModelRef, $reFieldNames)) {
                    $pivotData[$pivotModelRef] = $this->getCurrentModel()->{$modelRef};
                }
            }

            // Load reference model keys for item model
            foreach ($this->getItemReferences() as $pivotModelRef => $itemModelRef) {
                if (isset($itemModel->{$itemModelRef}) && in_array($pivotModelRef, $reFieldNames)) {
                    $pivotData[$pivotModelRef] = $itemModel->{$itemModelRef};
                }
            }

            // Save reference model
            $referenceModel->load($pivotData);
            if ($referenceModel->save()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get relation current model
     * @return Model
     */
    public function getCurrentModel(): Model
    {
        return $this->model;
    }

    /**
     * Get relation reference model
     * @return Model
     */
    public function getReferenceModel(): Model
    {
        return $this->pivotReference->getModel();
    }

    /**
     * Get relation references
     * @return array
     */
    public function getReferences(): array
    {
        return $this->pivotReference->getKeys();
    }

    /**
     * Get relation related model
     * @return Model
     */
    public function getItemModel(): Model
    {
        return $this->itemReference->getModel();
    }

    /**
     * Get relation related model references
     * @return array
     */
    public function getItemReferences(): array
    {
        return $this->itemReference->getKeys();
    }
}
