<?php

namespace Busarm\PhpMini\Interfaces\Data;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\CrudItemRequestDto;
use Busarm\PhpMini\Dto\CrudListRequestDto;
use Busarm\PhpMini\Dto\CrudPaginatedListRequestDto;
use Busarm\PhpMini\Interfaces\ResponseInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface CrudControllerInterface
{
    /**
     * Get item by id
     *
     * @param CrudItemRequestDto $dto
     * @return ResponseInterface
     */
    public function get(CrudItemRequestDto $dto): ResponseInterface;

    /**
     * Get list of items
     *
     * @param CrudListRequestDto $dto
     * @return ResponseInterface
     */
    public function list(CrudListRequestDto $dto): ResponseInterface;

    /**
     * Get paginated list of items
     * 
     * @param CrudPaginatedListRequestDto $dto
     * @return ResponseInterface
     */
    public function paginatedList(CrudPaginatedListRequestDto $dto): ResponseInterface;
    
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
