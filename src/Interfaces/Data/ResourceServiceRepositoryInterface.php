<?php

namespace Armie\Interfaces\Data;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Dto\PaginatedCollectionDto;
use Armie\Dto\ResourceItemRequestDto;
use Armie\Dto\ResourceListRequestDto;
use Armie\Dto\ResourcePaginatedListRequestDto;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ResourceServiceRepositoryInterface
{
    /**
     * Get item by id.
     *
     * @param ResourceItemRequestDto $dto
     *
     * @return ?BaseDto
     */
    public function get(ResourceItemRequestDto $dto): ?BaseDto;

    /**
     * Get list of items.
     *
     * @param ResourceListRequestDto $dto
     *
     * @return CollectionBaseDto<BaseDto>
     */
    public function list(ResourceListRequestDto $dto): CollectionBaseDto;

    /**
     * Get paginated list of items.
     *
     * @param ResourcePaginatedListRequestDto $dto
     *
     * @return PaginatedCollectionDto<BaseDto>
     */
    public function paginatedList(ResourcePaginatedListRequestDto $dto): PaginatedCollectionDto;

    /**
     * Create item record.
     *
     * @param BaseDto $dto
     *
     * @return bool
     */
    public function create(BaseDto $dto): bool;

    /**
     * Create bulk item records.
     *
     * @param CollectionBaseDto $dto
     *
     * @return bool
     */
    public function createBulk(CollectionBaseDto $dto): bool;

    /**
     * Update item record by id.
     *
     * @param int|string $id
     * @param BaseDto    $dto
     *
     * @return bool
     */
    public function update(int|string $id, BaseDto $dto): bool;

    /**
     * Update bulk item records.
     *
     * @param CollectionBaseDto $dto
     *
     * @return bool
     */
    public function updateBulk(CollectionBaseDto $dto): bool;

    /**
     * Delete item record by id.
     *
     * @param int|string $id
     *
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * Delete bulk item records.
     *
     * @param CollectionBaseDto $dto
     *
     * @return bool
     */
    public function deleteBulk(CollectionBaseDto $dto): bool;
}
