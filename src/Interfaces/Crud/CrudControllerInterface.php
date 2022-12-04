<?php

namespace Busarm\PhpMini\Interfaces\Crud;

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
    public function get(int|string $id): ResponseInterface;
    public function list(): ResponseInterface;
    public function paginatedList(): ResponseInterface;
    public function create(BaseDto $dto): ResponseInterface;
    public function createBulk(CollectionBaseDto $dto): ResponseInterface;
    public function update(int|string $id, BaseDto $dto): ResponseInterface;
    public function updateBulk(CollectionBaseDto $dto): ResponseInterface;
    public function delete(int|string $id): ResponseInterface;
    public function deleteBulk(CollectionBaseDto $dto): ResponseInterface;
}
