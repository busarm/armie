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
class OneToMany extends Relation
{
    protected array $sort = [];

    /**
     * @param string    $name      Relation attribute name in Current Model
     * @param Model     $model     Current Model
     * @param Reference $reference Relation Reference
     *
     * @return void
     */
    public function __construct(
        string $name,
        private Model $model,
        private Reference $reference
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
     * @return  self
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }
    
    /**
     * @inheritDoc
     *
     * @return Model[]
     */
    public function get(): array
    {
        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->getReferences() as $modelRef => $toModelRef) {
            if (($ref = $this->model->get($modelRef))) {
                $referenceConditions[] = "`$toModelRef` = :$toModelRef";
                $referenceParams[":$toModelRef"] = $ref;
            }
        }

        if (count($referenceConditions) && count($referenceConditions)) {
            return $this->getReferenceModel()->clone()->setAutoLoadRelations(false)->all(
                array_merge($referenceConditions, $this->conditions),
                array_merge($referenceParams, $this->params),
                $this->columns
            );
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
        foreach ($this->getReferences() as $modelRef => $toModelRef) {
            $refs = array_map(fn ($item) => $item->get($modelRef), $items);
            $referenceConditions[] = sprintf("`$toModelRef` IN (%s)", implode(',', array_fill(0, count($refs), '?')));
            $referenceParams = array_merge($referenceParams, $refs);
        }

        if (count($referenceConditions) && count($referenceConditions)) {
            // Get relation results for all items
            $results = $this->getReferenceModel()->clone()->setAutoLoadRelations(false)->setPerPage($this->limit * count($items))->all(
                array_merge($referenceConditions, $this->conditions),
                array_merge($referenceParams, $this->params),
                $this->columns
            );

            // Group result for references
            $resultsMap = [];
            foreach ($results as $result) {
                $key = implode('-', array_map(fn ($ref) => $result->get($ref), array_values($this->getReferences())));
                $resultsMap[$key][] = $result;  // Multiple items (1:m)
            }

            // Map relation for each item
            foreach ($items as &$item) {
                $key = implode('-', array_map(fn ($ref) => $item->get($ref), array_keys($this->getReferences())));
                $item->set($this->getName(), $resultsMap[$key] ?? []);
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

        $referenceModel = $this->getReferenceModel()->clone();

        // Load reference model keys in to $data if available
        $reFieldNames = $referenceModel->getFieldNames();
        foreach ($this->getReferences() as $modelRef => $toModelRef) {
            if (!isset($data[$toModelRef]) && ($ref = $this->getCurrentModel()->get($modelRef)) && in_array($toModelRef, $reFieldNames)) {
                $data[$toModelRef] = $ref;
            }
        }

        // Save reference model
        $referenceModel->fastLoad($data);
        if ($referenceModel->save()) {
            $modelData = [];

            // Load current model keys
            $fieldNames = $this->getCurrentModel()->getFieldNames();
            foreach ($this->getReferences() as $modelRef => $toModelRef) {
                if (($ref = $referenceModel->get($toModelRef)) && in_array($modelRef, $fieldNames)) {
                    $modelData[$modelRef] = $ref;
                }
            }

            // Save current model keys
            if (!empty($modelData) || ($this->getCurrentModel()->get($this->getCurrentModel()->getKeyName()))) {
                $this->getCurrentModel()->fastLoad($modelData);
                $this->getCurrentModel()->save(false, false);
            }

            return true;
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
        return $this->reference->getModel($this->model->getDatabase());
    }

    /**
     * @inheritDoc
     */
    public function getReferences(): array
    {
        return $this->reference->getKeys();
    }
}
