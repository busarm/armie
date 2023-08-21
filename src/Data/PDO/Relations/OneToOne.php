<?php

namespace Armie\Data\PDO\Relations;

use Armie\Data\PDO\Model;
use Armie\Data\PDO\Reference;
use Armie\Data\PDO\Relation;
use Armie\Enums\DataType;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class OneToOne extends Relation
{
    /**
     * @param string $name Relation attribute name in Current Model
     * @param Model $model Current Model 
     * @param Reference $reference Relation Reference
     * @return void
     */
    public function __construct(
        private string $name,
        private Model $model,
        private Reference $reference,
    ) {
        parent::__construct($name, DataType::OBJECT);
    }

    /**
     * @inheritDoc
     * @return Model|null
     */
    public function get(): ?Model
    {
        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->getReferences() as $modelRef => $toModelRef) {
            if (isset($this->model->{$modelRef})) {
                $referenceConditions[] = "`$toModelRef` = :$toModelRef";
                $referenceParams[":$toModelRef"] = $this->model->{$modelRef};
            }
        }

        if (count($referenceConditions) && count($referenceConditions)) {
            return $this->getReferenceModel()->clone()->setAutoLoadRelations(false)->findWhere(
                array_merge($referenceConditions, $this->conditions),
                array_merge($referenceParams, $this->params),
                $this->columns
            );
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function load(array $items): array
    {
        if (empty($items)) return [];

        $referenceConditions = [];
        $referenceParams = [];
        foreach ($this->getReferences() as $modelRef => $toModelRef) {
            $refs = array_map(fn ($item) => $item->{$modelRef}, $items);
            $referenceConditions[] = sprintf("`$toModelRef` IN (%s)", implode(',', array_fill(0, count($refs), '?')));
            $referenceParams = array_merge($referenceParams, $refs);
        }

        if (count($referenceConditions) && count($referenceConditions)) {

            // Get relation results for all items
            $results = $this->getReferenceModel()->clone()->setAutoLoadRelations(false)->setPerPage(count($items))->all(
                array_merge($referenceConditions, $this->conditions),
                array_merge($referenceParams, $this->params),
                $this->columns
            );

            // Group result for references
            $resultsMap = [];
            foreach ($results as $result) {
                $key = implode('-', array_map(fn ($ref) => $result->{$ref}, array_values($this->getReferences())));
                $resultsMap[$key] = $result;  // Single item (1:1)
            }

            // Map relation for each item
            foreach ($items as &$item) {
                $key = implode('-', array_map(fn ($ref) => $item->{$ref}, array_keys($this->getReferences())));
                $item->{$this->getName()} = $resultsMap[$key] ?? null;
                $item->addLoadedRelation($this->getName());
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
        if (empty($data)) return false;

        // Is multiple values - select last, as 1:1 relation only supports single item
        if (array_is_list($data)) {
            $data = $data[count($data) - 1];
        }

        $referenceModel = $this->getReferenceModel()->clone();

        // Load reference model keys in to $data if available
        $reFieldNames = $referenceModel->getFieldNames();
        foreach ($this->getReferences() as $modelRef => $toModelRef) {
            if (!isset($data[$toModelRef]) && isset($this->getCurrentModel()->{$modelRef}) && in_array($toModelRef, $reFieldNames)) {
                $data[$toModelRef] = $this->getCurrentModel()->{$modelRef};
            }
        }

        // Save reference model
        $referenceModel->fastLoad($data);
        if ($referenceModel->save()) {

            $modelData = [];

            // Load current model keys
            $fieldNames = $this->getCurrentModel()->getFieldNames();
            foreach ($this->getReferences() as $modelRef => $toModelRef) {
                if (isset($referenceModel->{$toModelRef}) && in_array($modelRef, $fieldNames)) {
                    $modelData[$modelRef] = $referenceModel->{$toModelRef};
                }
            }

            // Save current model keys
            if (!empty($modelData) || isset($this->getCurrentModel()->{$this->getCurrentModel()->getKeyName()})) {
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
