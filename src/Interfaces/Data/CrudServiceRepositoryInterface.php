<?php

namespace Busarm\PhpMini\Interfaces\Data;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\CrudItemRequestDto;
use Busarm\PhpMini\Dto\CrudListRequestDto;
use Busarm\PhpMini\Dto\CrudPaginatedListRequestDto;
use Busarm\PhpMini\Dto\PaginatedCollectionDto;
use Busarm\PhpMini\Interfaces\ResponseInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface CrudServiceRepositoryInterface
{
    /**
     * Get item by id
     *
     * @param CrudItemRequestDto $dto
     * @return ?BaseDto
     */
    public function get(CrudItemRequestDto $dto): ?BaseDto;

    /**
     * Get list of items
     *
     * @param CrudListRequestDto $dto
     * @return CollectionBaseDto
     */
    public function list(CrudListRequestDto $dto): CollectionBaseDto;

    /**
     * Get paginated list of items
     * 
     * @param CrudPaginatedListRequestDto $dto
     * @return PaginatedCollectionDto
     */
    public function paginatedList(CrudPaginatedListRequestDto $dto): PaginatedCollectionDto;

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
