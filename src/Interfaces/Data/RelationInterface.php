<?php

namespace Busarm\PhpMini\Interfaces\Data;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface RelationInterface
{

    /**
     * Get relation references
     * @return array
     */
    public function getReferences(): array;

    /**
     * Get relation reference model
     * @return ModelInterface
     */
    public function getReferenceModel(): ModelInterface;

    /**
     * Get relation current model
     * @return ModelInterface
     */
    public function getCurrentModel(): ModelInterface;

    /**
     * Get relation data
     * 
     * @return ModelInterface[]|ModelInterface|null
     */
    public function get(): array|ModelInterface|null;

    /**
     * Load relation data for list of items
     * 
     * @param ModelInterface[] $items
     * @return ModelInterface[] $items with loaded relations
     */
    public function load(array $items): array;

    /**
     * Save relation data
     * 
     * @param array $data
     * @return bool
     */
    public function save(array $data): bool;
}
