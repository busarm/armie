<?php

namespace Armie\Interfaces\Data;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Dto\PaginatedCollectionDto;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 *
 * @template ModelType
 * @template DtoType
 */
interface RepositoryInterface
{
    /**
     * Get all records.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params
     * @param array $columns    Select Colomn names
     * @param array $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int   $limit      Query Limit. Default: 0 to use `perPage`
     *
     * @return CollectionBaseDto<ModelType|DtoType>
     */
    public function all(array $conditions = [], array $params = [], array $columns = [], array $sort = [], int $limit = 0): CollectionBaseDto;

    /**
     * Get all records with trashed.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params
     * @param array $columns    Select Colomn names
     * @param array $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int   $limit      Query Limit. Default: 0 to use `perPage`
     *
     * @return CollectionBaseDto<ModelType|DtoType>
     */
    public function allTrashed(array $conditions = [], array $params = [], array $columns = [], array $sort = [], int $limit = 0): CollectionBaseDto;

    /**
     * Get paginated list of records.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params
     * @param array $columns    Select Colomn names
     * @param array $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int   $page       Page Number Default: 1
     * @param int   $limit      Page Limit. Default: 0 to disable
     *
     * @return PaginatedCollectionDto<ModelType|DtoType>
     */
    public function paginate(array $conditions = [], array $params = [], array $columns = [], array $sort = [], int $page = 1, int $limit = 0): PaginatedCollectionDto;

    /**
     * Find record by id.
     *
     * @param int|string $id
     * @param array      $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array      $params     Query Params. e.g SQL query bind params
     * @param array      $columns    Select Colomn names
     *
     * @return (BaseDto&DtoType)|null
     */
    public function findById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto;

    /**
     * Find with trashed record by id.
     *
     * @param int|string $id
     * @param array      $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array      $params     Query Params. e.g SQL query bind params
     * @param array      $columns    Select Colomn names
     *
     * @return (BaseDto&DtoType)|null
     */
    public function findTrashedById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto;

    /**
     * Create a record.
     *
     * @param array $data
     *
     * @return (BaseDto&DtoType)|null
     */
    public function create(array $data): ?BaseDto;

    /**
     * Create list of records.
     *
     * @param array $data
     *
     * @return bool
     */
    public function createBulk(array $data): bool;

    /**
     * Update record by id.
     *
     * @param int|string $id
     * @param array      $data
     *
     * @return bool
     */
    public function updateById(int|string $id, array $data): bool;

    /**
     * Update list of records.
     *
     * @param array $data
     *
     * @return bool
     */
    public function updateBulk(array $data): bool;

    /**
     * Delete record by id.
     *
     * @param int|string $id
     * @param bool       $force Permanently delete
     *
     * @return bool
     */
    public function deleteById(int|string $id, $force = false): bool;

    /**
     * Delete list of records.
     *
     * @param array $ids
     * @param bool  $force Permanently delete
     *
     * @return bool
     */
    public function deleteBulk(array $ids, $force = false): bool;

    /**
     * Restore record by id.
     *
     * @param int|string $id
     *
     * @return bool
     */
    public function restoreById(int|string $id): bool;

    /**
     * Restore list of records.
     *
     * @param array $ids
     *
     * @return bool
     */
    public function restoreBulk(array $ids): bool;
}
