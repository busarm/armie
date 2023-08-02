<?php

namespace Busarm\PhpMini\Interfaces\Data;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\ResourceItemRequestDto;
use Busarm\PhpMini\Dto\ResourceListRequestDto;
use Busarm\PhpMini\Dto\ResourcePaginatedListRequestDto;
use Busarm\PhpMini\Dto\PaginatedCollectionDto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ResourceServiceRepositoryInterface
{
    /**
     * Get item by id
     *
     * @param ResourceItemRequestDto $dto
     * @return ?BaseDto
     */
    public function get(ResourceItemRequestDto $dto): ?BaseDto;

    /**
     * Get list of items
     *
     * @param ResourceListRequestDto $dto
     * @return CollectionBaseDto
     */
    public function list(ResourceListRequestDto $dto): CollectionBaseDto;

    /**
     * Get paginated list of items
     * 
     * @param ResourcePaginatedListRequestDto $dto
     * @return PaginatedCollectionDto
     */
    public function paginatedList(ResourcePaginatedListRequestDto $dto): PaginatedCollectionDto;

    /**
     * Create item record
     *
     * @param BaseDto $dto
     * @return bool
     */
    public function create(BaseDto $dto): bool;
    /**
     * Create bulk item records
     *
     * @param CollectionBaseDto $dto
     * @return bool
     */
    public function createBulk(CollectionBaseDto $dto): bool;
    /**
     * Update item record by id
     *
     * @param integer|string $id
     * @param BaseDto $dto
     * @return bool
     */
    public function update(int|string $id, BaseDto $dto): bool;
    /**
     * Update bulk item records
     *
     * @param CollectionBaseDto $dto
     * @return bool
     */
    public function updateBulk(CollectionBaseDto $dto): bool;
    /**
     * Delete item record by id
     *
     * @param integer|string $id
     * @return bool
     */
    public function delete(int|string $id): bool;
    /**
     * Delete bulk item records
     *
     * @param CollectionBaseDto $dto
     * @return bool
     */
    public function deleteBulk(CollectionBaseDto $dto): bool;
}
