<?php

namespace Busarm\PhpMini\Interfaces\HTTP;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
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
     * @param integer|string $id
     * @http-query array query - Reqested query conditions. E.g ['name' => 'splendy1']
     * @http-query array columns - Reqested columns. E.g ['name', 'age']
     * 
     * @return ResponseInterface
     */
    public function get(int|string $id): ResponseInterface;
    /**
     * Get list of items
     * 
     * @http-query array query - Reqested query conditions. E.g ['name' => 'splendy1']
     * @http-query array columns - Reqested columns. E.g ['name', 'age']
     * @http-query int page - Reqested page
     * @http-query int limit - List limit
     *
     * @return ResponseInterface
     */
    public function list(): ResponseInterface;
    /**
     * Get paginated list of items
     * 
     * @http-query array query - Reqested query conditions. E.g ['name' => 'splendy1']
     * @http-query array columns - Reqested columns. E.g ['name', 'age']
     * @http-query int page - Reqested page
     * @http-query int limit - List limit
     *
     * @return ResponseInterface
     */
    public function paginatedList(): ResponseInterface;
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
