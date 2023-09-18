<?php

namespace Armie\Data\PDO\Relations;

use Armie\Data\PDO\Model;
use Armie\Data\PDO\Reference;
use Armie\Data\PDO\Relation;
use Armie\Enums\DataType;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @inheritDoc
 */
class ManyToMany extends Relation
{
    const MODE_PIVOT = 1;
    const MODE_ITEM  = 2;

    protected array $sort = [];

    /**
     * @param string    $name           Relation attribute name in Current Model
     * @param Model     $model          Current Model
     * @param Reference $pivotReference Pivot Relation Reference
     * @param Reference $itemReference  Item Relation Reference
     * @param self::MODE_PIVOT|self::MODE_ITEM $mode    Relation retrieval mode. 
     * - `self::MODE_ITEM`  - Retrieve `item` content only. 
     * - `self::MODE_PIVOT` - Retrieve `pivot` content only
     *
     * @return void
     */
    public function __construct(
        string $name,
        private Model $model,
        private Reference $pivotReference,
        private Reference $itemReference,
        protected int $mode = self::MODE_PIVOT,
    ) {
        parent::__construct($name, DataType::ARRAY);
    }

    /**
     * Get the value of sort
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set the value of sort - List of sorting columns or conditions. e.g `['name' => 'ASC']` or ['name ASC']
     * 
     * @param array $sort Columns SHOULD be part of `pivot` table
     *
     * @return  self
     */
    public function setSort(array $sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get relation retrieval mode
     *
     * @return self::MODE_PIVOT|self::MODE_ITEM
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set relation retrieval mode
     *
     * @param self::MODE_PIVOT|self::MODE_ITEM  $mode
     * 
     * `MODE_ITEM`  - Retrieve `item` content only
     * `MODE_PIVOT` - Retrieve `pivot` content only
     *
     * @return  self
     */
    public function setMode(int $mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get item relation in pivot model to be loaded
     * * Only load relation if it's linked to item model (self::getItemModel)
     * * Ensure that relation is a One to One relation to avoid infinite loops.
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
     * @inheritDoc
     *
     * @return Model[]
     */
    public function get(array $conditions = []): array
    {
        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->getReferences() as $modelRef => $pivotModelRef) {
            if ($this->model->get($modelRef)) {
                $referenceConditions[] = "`$pivotModelRef` = :$pivotModelRef";
                $referenceParams[":$pivotModelRef"] = $this->model->get($modelRef);
            }
        }

        $itemRelationName = $this->getItemRelationName();
        if (count($referenceConditions) && count($referenceParams)) {
            $model = $this->getReferenceModel()->clone()->setAutoLoadRelations(false);
            if ($itemRelationName && $this->mode == self::MODE_ITEM) {
                $model->setRequestedRelations([
                    $itemRelationName => function (Relation $relation) {
                        $relation->setConditions($this->conditions)
                            ->setParams($this->params)
                            ->setColumns($this->columns);
                    },
                ]);
            }
            $results = $model->all(
                conditions: array_merge($referenceConditions, $this->mode == self::MODE_PIVOT ? $this->conditions : []),
                params: array_merge($referenceParams, $this->mode == self::MODE_PIVOT ? $this->params : []),
                columns: $this->mode == self::MODE_PIVOT ? $this->columns : [],
                limit: $this->limit,
                sort: $this->sort
            );

            if ($this->mode == self::MODE_ITEM && $itemRelationName) {
                $results = array_reduce($results, function ($carry, Model $current) use ($itemRelationName) {
                    $carry[] = $current->get($itemRelationName);
                    return $carry;
                }, []);
            }

            return $results;
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function load(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->getReferences() as $modelRef => $pivotModelRef) {
            $refs = array_map(fn ($item) => $item->get($modelRef), $items);
            $referenceConditions[] = sprintf("`$pivotModelRef` IN (%s)", implode(',', array_fill(0, count($refs), '?')));
            $referenceParams = array_merge($referenceParams, $refs);
        }

        if (count($referenceConditions) && count($referenceParams)) {
            // Get relation results for all items
            $results = $this->getReferenceModel()->clone()
                ->setAutoLoadRelations(false)
                ->itterate(
                    conditions: array_merge($referenceConditions, $this->mode == self::MODE_PIVOT ? $this->conditions : []),
                    params: array_merge($referenceParams, $this->mode == self::MODE_PIVOT ? $this->params : []),
                    columns: $this->mode == self::MODE_PIVOT ? $this->columns : [],
                    limit: $this->limit,
                    sort: $this->sort,
                );

            $fromRefKeys = array_keys($this->getReferences());
            $toRefKeys = array_values($this->getReferences());

            // Group result for references
            $resultsMap = [];
            foreach ($results as $result) {
                $key = implode('-', array_map(fn ($ref) => $result->get($ref), $toRefKeys));
                $resultsMap[$key][] = $result; // Multiple items (m:m)
            }

            // Map relation for each item
            foreach ($items as &$item) {
                $key = implode('-', array_map(fn ($ref) => $item->get($ref), $fromRefKeys));
                $data = $resultsMap[$key] ?? [];

                // Get item relation name
                $itemRelationName = $this->getItemRelationName();
                // Eager Load item relation for result data
                if ($itemRelationName && $this->mode == self::MODE_ITEM) {
                    $data = $this->getReferenceModel()->clone()
                        ->setRequestedRelations([
                            $itemRelationName => function (Relation $relation) {
                                $relation->setConditions($this->conditions)
                                    ->setParams($this->params)
                                    ->setColumns($this->columns);
                            },
                        ])
                        ->eagerLoadRelations($data);

                    // Get item relation if item mode
                    $data = array_reduce($data, function ($carry, Model $current) use ($itemRelationName) {
                        $carry[] = $current->get($itemRelationName);
                        return $carry;
                    }, []);
                }

                $item->set($this->getName(), $data);
                $item->addLoadedRelation($this->getName());
                $item->select([...$item->selected(), $this->getName()]);
            }

            return $items;
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function save(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // Is multiple values
        if (array_is_list($data)) {
            return $this->getReferenceModel()->transaction(function () use ($data) {
                $done = true;
                foreach ($data as $item) {
                    if (is_array($item)) {
                        if (!$this->save($item)) {
                            $done = false;
                        }
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
                if (($ref = $this->getCurrentModel()->get($modelRef)) && in_array($pivotModelRef, $reFieldNames)) {
                    $pivotData[$pivotModelRef] = $ref;
                }
            }

            // Load reference model keys for item model
            foreach ($this->getItemReferences() as $pivotModelRef => $itemModelRef) {
                if (($ref = $itemModel->get($itemModelRef)) && in_array($pivotModelRef, $reFieldNames)) {
                    $pivotData[$pivotModelRef] = $ref;
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
     * @inheritDoc
     */
    public function getCurrentModel(): Model
    {
        return $this->model;
    }

    /**
     * @inheritDoc
     */
    public function getReferenceModel(): Model
    {
        return $this->pivotReference->getModel($this->model->getDatabase());
    }

    /**
     * @inheritDoc
     */
    public function getReferences(): array
    {
        return $this->pivotReference->getKeys();
    }

    /**
     * Get relation related model.
     *
     * @return Model
     */
    public function getItemModel(): Model
    {
        return $this->itemReference->getModel($this->model->getDatabase());
    }

    /**
     * Get relation related model references.
     *
     * @return array
     */
    public function getItemReferences(): array
    {
        return $this->itemReference->getKeys();
    }
}
