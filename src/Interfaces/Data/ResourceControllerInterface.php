<?php

namespace Busarm\PhpMini\Interfaces\Data;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\ResourceItemRequestDto;
use Busarm\PhpMini\Dto\ResourceListRequestDto;
use Busarm\PhpMini\Dto\ResourcePaginatedListRequestDto;
use Busarm\PhpMini\Interfaces\ResponseInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
