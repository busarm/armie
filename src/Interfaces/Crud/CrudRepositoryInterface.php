<?php

namespace Busarm\PhpMini\Interfaces\Crud;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\PaginatedCollectionDto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface CrudRepositoryInterface
{
    /**
     * Count number of rows in query
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params
     * @return int
     */
    public function count(string $query, $params = array()): int;

    /**
     * Execute query.
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params
     * @return int|bool Returns row count for modification query or boolean success status
     */
    public function query(string $query, array $params = array()): int|bool;

    /**
     * Find single model with query.
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params
     * @return BaseDto|null
     */
    public function querySingle(string $query, $params = []): ?BaseDto;

    /**
     * Find list of models with query.
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params
     * @return CollectionBaseDto
     */
    public function queryList(string $query, $params = []): CollectionBaseDto;

    /**
     * Find list of models with paginated query.
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params
     * @param int $page Page Number Default: 1
     * @param int $limit Page Limit. Default: 0 to disable
     * @return PaginatedCollectionDto
     */
    public function queryPaginate(string $query, $params = [], int $page = 1, int $limit = 0): PaginatedCollectionDto;

    /**
     * Get all models.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names
     * @return CollectionBaseDto
     */
    public function all(array $conditions = [], array $params = [], array $columns = []): CollectionBaseDto;

    /**
     * Get all models with trashed.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names
     * @return CollectionBaseDto
     */
    public function allTrashed(array $conditions = [], array $params = [], array $columns = []): CollectionBaseDto;

    /**
     * Get paginated list of models.
     *
     * @param int $page Page Number Default: 1
     * @param int $limit Page Limit. Default: 0 to disable
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names
     * @return PaginatedCollectionDto
     */
    public function paginate(int $page = 1, int $limit = 0, array $conditions = [], array $params = [], array $columns = []): PaginatedCollectionDto;

    /**
     * Find model by id.
     *
     * @param int|string $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names
     * @return BaseDto|null
     */
    public function findById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto;

    /**
     * Find with trashed model by id. 
     *
     * @param int|string $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names
     * @return BaseDto|null
     */
    public function findTrashedById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto;

    /**
     * Create a model.
     *
     * @param array $data
     * @return BaseDto|null
     */
    public function create(array $data): ?BaseDto;

    /**
     * Create list of models.
     *
     * @param array $data
     * @return bool
     */
    public function createBulk(array $data): bool;

    /**
     * Update model by id.
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function updateById(int|string $id, array $data): bool;

    /**
     * Update list of models.
     *
     * @param array $data
     * @return bool
     */
    public function updateBulk(array $data): bool;

    /**
     * Delete model by id.
     *
     * @param int|string $id
     * @param bool $force Permanently delete
     * @return bool
     */
    public function deleteById(int|string $id, $force = false): bool;

    /**
     * Delete list of models.
     *
     * @param array $ids
     * @param bool $force Permanently delete
     * @return bool
     */
    public function deleteBulk(array $ids, $force = false): bool;

    /**
     * Restore model by id.
     *
     * @param int|string $id
     * @return bool
     */
    public function restoreById(int|string $id): bool;

    /**
     * Restore list of models.
     *
     * @param array $ids
     * @return bool
     */
    public function restoreBulk(array $ids): bool;
}
