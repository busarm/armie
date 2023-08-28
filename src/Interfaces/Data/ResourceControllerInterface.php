<?php

namespace Armie\Interfaces\Data;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Dto\ResourceItemRequestDto;
use Armie\Dto\ResourceListRequestDto;
use Armie\Dto\ResourcePaginatedListRequestDto;
use Armie\Interfaces\ResponseInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ResourceControllerInterface
{
    /**
     * Get item by id
     *
     * @param ResourceItemRequestDto $dto
     * @return ResponseInterface
     */
    public function get(ResourceItemRequestDto $dto): ResponseInterface;

    /**
     * Get list of items
     *
     * @param ResourceListRequestDto $dto
     * @return ResponseInterface
     */
    public function list(ResourceListRequestDto $dto): ResponseInterface;

    /**
     * Get paginated list of items
     * 
     * @param ResourcePaginatedListRequestDto $dto
     * @return ResponseInterface
     */
    public function paginatedList(ResourcePaginatedListRequestDto $dto): ResponseInterface;
    
    /**
     * Create item record
     *
     * @param BaseDto $dto
     * @return ResponseInterface
     */
    public function create(BaseDto $dto): ResponseInterface;
    /**
     * Create bulk item records
     *
     * @param CollectionBaseDto $dto
     * @return ResponseInterface
     */
    public function createBulk(CollectionBaseDto $dto): ResponseInterface;
    /**
     * Update item record by id
     *
     * @param integer|string $id
     * @param BaseDto $dto
     * @return ResponseInterface
     */
    public function update(int|string $id, BaseDto $dto): ResponseInterface;
    /**
     * Update bulk item records
     *
     * @param CollectionBaseDto $dto
     * @return ResponseInterface
     */
    public function updateBulk(CollectionBaseDto $dto): ResponseInterface;
    /**
     * Delete item record by id
     *
     * @param integer|string $id
     * @return ResponseInterface
     */
    public function delete(int|string $id): ResponseInterface;
    /**
     * Delete bulk item records
     *
     * @param CollectionBaseDto $dto
     * @return ResponseInterface
     */
    public function deleteBulk(CollectionBaseDto $dto): ResponseInterface;
}
