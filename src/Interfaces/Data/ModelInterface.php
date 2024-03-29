<?php

namespace Armie\Interfaces\Data;

use Generator;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ModelInterface
{
    /**
     * Model table name. e.g db table, collection name.
     *
     * @return string
     */
    public function getTableName(): string;

    /**
     * Model key name. e.g table primary key, unique index.
     *
     * @return string|null
     */
    public function getKeyName(): ?string;

    /**
     * Model relations.
     *
     * @return \Armie\Interfaces\Data\RelationInterface[]
     */
    public function getRelations(): array;

    /**
     * Model fields.
     *
     * @return \Armie\Interfaces\Data\FieldInterface[]
     */
    public function getFields(): array;

    /**
     * Model created date param name. e.g created_at, createdAt.
     *
     * @return string
     */
    public function getCreatedDateName(): ?string;

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt.
     *
     * @return string
     */
    public function getUpdatedDateName(): ?string;

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt.
     *
     * @return string
     */
    public function getSoftDeleteDateName(): ?string;

    /**
     * Check if model was soft deleted.
     *
     * @return bool
     */
    public function isTrashed(): bool;

    /**
     * Count total number of model items.
     *
     * @param string|null $query  Custom query to count
     * @param array       $params Custom query params
     *
     * @return int
     */
    public function count(string|null $query = null, $params = []): int;

    /**
     * Find model for id. Without trashed (deleted) models.
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
     * @param array $columns    Select Colomn names.
     *
     * @return self|null
     */
    public function find($id, $conditions = [], $params = [], $columns = []): ?self;

    /**
     * Find model for id. With trashed (deleted) models.
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
     * @param array $columns    Select Colomn names.
     *
     * @return self|null
     */
    public function findTrashed($id, $conditions = [], $params = [], $columns = []): ?self;

    /**
     * Find model with condition.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params `[$id]` or [':id' => $id]
     * @param array $columns    Select Colomn names.
     *
     * @return self|null
     */
    public function findWhere($conditions = [], $params = [], $columns = []): ?self;

    /**
     * Get list of model. Without trashed (deleted) models.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params
     * @param array $columns    Select Colomn names.
     * @param array $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int   $limit      Query Limit. Default: 0 to use `perPage`
     * @param int   $page       Query List Page.
     *
     * @return self[]
     */
    public function all($conditions = [], $params = [], $columns = [], array $sort = [], int $limit = 0, int $page = 0): array;

    /**
     * Get list of model. With trashed (deleted) models.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params
     * @param array $columns    Select Colomn names.
     * @param array $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int   $limit      Query Limit. Default: 0 to use `perPage`
     * @param int   $page       Query List Page.
     *
     * @return self[]
     */
    public function allTrashed($conditions = [], $params = [], $columns = [], array $sort = [], int $limit = 0, int $page = 0): array;

    /**
     * Itterate upon list of model. With trashed (deleted) models.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]` or `['>=' => ['age'=>18]]` or `['AND/OR/NOT' => ['age'=>18]]`. Must not include un-escaped query keywords like: select,where,from etc.
     * @param array $params     Query Params. e.g SQL query bind params
     * @param array $columns    Select Colomn names.
     * @param array $sort       Sort Result. e.g `['name' => 'ASC']`
     * @param int   $limit      Query Limit. Default: 0 to use `perPage`
     * @param int   $page       Query List Page.
     *
     * @return Generator<int,static>
     */
    public function itterate($conditions = [], $params = [], $columns = [], array $sort = [], int $limit = 0, int $page = 0): Generator;

    /**
     * Delete model.
     *
     * @param bool $force Force permanent delete or soft delete if supported
     *
     * @return bool
     */
    public function delete($force = false): bool;

    /**
     * Restore model.
     *
     * @return bool
     */
    public function restore(): bool;

    /**
     * Save model.
     *
     * @param bool $trim      Exclude NULL properties before saving
     * @param bool $relations Save relations if available
     *
     * @return bool
     */
    public function save($trim = false, $relations = true): bool;

    /**
     * Clone model.
     *
     * @return static
     */
    public function clone();
}
