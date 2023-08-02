<?php

namespace Busarm\PhpMini\Data\PDO\Relations;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Data\PDO\Reference;
use Busarm\PhpMini\Data\PDO\Relation;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Enums\DataType;
use Busarm\PhpMini\Interfaces\Data\ModelInterface;

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
     * @param bool $fullMode Defaut: `true`. Get or Load full relation data with pivot relation or only item relation
     * @return void
     */
    public function __construct(
        private string $name,
        private Model $model,
        private Reference $pivotReference,
        private Reference $itemReference,
        private bool $fullMode = true
    ) {
        parent::__construct($name, DataType::ARRAY);
    }

    /**
     * Set the value of fullMode
     *
     * @return  self
     */
    public function setFullMode(bool $fullMode)
    {
        $this->fullMode = $fullMode;

        return $this;
    }

    /**
     * Get item relation in pivot model to be loaded
     * * Only load relation if it's linked to item model (self::getItemModel)
     * * Ensure that relation is a One to One relation to avoid infinite loops
     * 
     * @return string|null
     */
    private function getItemRelationName(): ?string
    {
        foreach ($this->getReferenceModel()->getRelations() as $relation) {
            if (($relation instanceof OneToOne) && get_class($this->getItemModel()) === get_class($relation->getReferenceModel())) {
                return $relation->getName();
            }
        }
        return null;
    }

    /**
     * Get relation data
     * 
     * @return ModelInterface[]
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

        $itemRelationName = $this->getItemRelationName();
        if (count($referenceConditions) && count($referenceParams)) {
            $results = $this->getReferenceModel()
                ->withRelations($itemRelationName ? ($this->fullMode ? [$itemRelationName] : [
                    $itemRelationName => function (Relation $relation) {
                        $relation->setConditions($this->conditions)
                            ->setParams($this->params)
                            ->setColumns($this->columns)
                            ->setLimit($this->limit);
                    }
                ]) : [])
                ->setAutoLoadRelations(false)
                ->setPerPage($this->fullMode ? $this->limit : -1)
                ->all(
                    array_merge($referenceConditions, $this->fullMode ? $this->conditions : []),
                    array_merge($referenceParams, $this->fullMode ? $this->params : []),
                    $this->fullMode ? $this->columns : []
                );

            if (!$this->fullMode && $itemRelationName) {
                return CollectionBaseDto::of($results)->pluck($itemRelationName);
            }
            return $results;
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
                ->clone()
                ->setAutoLoadRelations(false)
                ->setPerPage($this->fullMode ? $this->limit * count($items) : -1)
                ->all(
                    array_merge($referenceConditions, $this->fullMode ? $this->conditions : []),
                    array_merge($referenceParams, $this->fullMode ? $this->params : []),
                    $this->fullMode ? $this->columns : []
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
                $data = $resultsMap[$key] ?? [];

                // Get item relation name
                $itemRelationName = $this->getItemRelationName();

                // Eager Load item relation for result data
                $data = $this->getReferenceModel()
                    ->withRelations($itemRelationName ? ($this->fullMode ? [$itemRelationName] : [
                        $itemRelationName => function (Relation $relation) {
                            $relation->setConditions($this->conditions)
                                ->setParams($this->params)
                                ->setColumns($this->columns)
                                ->setLimit($this->limit);
                        }
                    ]) : [])->eagerLoadRelations($data);

                // Get item relation if full mode not supported
                if (!$this->fullMode && $itemRelationName) {
                    $data = CollectionBaseDto::of($data)->pluck($itemRelationName);
                }

                $item->{$this->getName()} = $data;
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
        $itemModel->fastLoad($data);
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
            $referenceModel->fastLoad($pivotData);
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
        return $this->pivotReference->getModel($this->model->getDatabase());
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
        return $this->itemReference->getModel($this->model->getDatabase());
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
