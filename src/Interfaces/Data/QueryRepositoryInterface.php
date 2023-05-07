<?php

namespace Busarm\PhpMini\Interfaces\Data;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\PaginatedCollectionDto;
use Busarm\PhpMini\Interfaces\Data\RepositoryInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface QueryRepositoryInterface extends RepositoryInterface
{
    /**
     * Count number of rows or rows in query
     *
     * @param string|null $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params
     * @return int
     */
    public function count(string|null $query = null, $params = array()): int;

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
}