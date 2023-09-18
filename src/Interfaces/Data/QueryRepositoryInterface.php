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
interface QueryRepositoryInterface extends RepositoryInterface
{
    /**
     * Count number of rows or rows in query.
     *
     * @param string|null $query  Model Provider Query. e.g SQL query
     * @param array       $params Query Params. e.g SQL query bind params
     *
     * @return int
     */
    public function count(string|null $query = null, $params = []): int;

    /**
     * Execute query.
     *
     * @param string $query  Model Provider Query. e.g SQL query
     * @param array  $params Query Params. e.g SQL query bind params
     *
     * @return int|bool Returns row count for modification query or boolean success status
     */
    public function query(string $query, array $params = []): int|bool;

    /**
     * Find single model with query.
     *
     * @param string $query  Model Provider Query. e.g SQL query
     * @param array  $params Query Params. e.g SQL query bind params
     *
     * @return (BaseDto&DtoType)|null
     */
    public function querySingle(string $query, $params = []): ?BaseDto;

    /**
     * Find list of models with query.
     *
     * @param string $query  Model Provider Query. e.g SQL query
     * @param array  $params Query Params. e.g SQL query bind params
     * @param int    $limit  Query Limit. Default: 0 to use `perPage`
     *
     * @return CollectionBaseDto<ModelType|DtoType>
     */
    public function queryList(string $query, $params = [], int $limit = 0): CollectionBaseDto;

    /**
     * Find list of models with paginated query.
     *
     * @param string $query  Model Provider Query. e.g SQL query
     * @param array  $params Query Params. e.g SQL query bind params
     * @param int    $page   Page Number Default: 1
     * @param int    $limit  Page Limit. Default: 0 to disable
     *
     * @return PaginatedCollectionDto<ModelType|DtoType>
     */
    public function queryPaginate(string $query, $params = [], int $page = 1, int $limit = 0): PaginatedCollectionDto;
}
