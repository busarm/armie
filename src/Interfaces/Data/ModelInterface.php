<?php

namespace Busarm\PhpMini\Interfaces\Data;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ModelInterface
{

    /**
     * Model table name. e.g db table, collection name
     *
     * @return string
     */
    public function getTableName(): string;

    /**
     * Model key name. e.g table primary key, unique index
     *
     * @return string|null
     */
    public function getKeyName(): ?string;

    /**
     * Model relations.
     *
     * @return \Busarm\PhpMini\Interfaces\Data\RelationInterface[]
     */
    public function getRelations(): array;

    /**
     * Model fields
     *
     * @return \Busarm\PhpMini\Interfaces\Data\FieldInterface[]
     */
    public function getFields(): array;


    /**
     * Model created date param name. e.g created_at, createdAt
     *
     * @return string
     */
    public function getCreatedDateName(): ?string;

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt
     *
     * @return string
     */
    public function getUpdatedDateName(): ?string;

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt
     *
     * @return string
     */
    public function getSoftDeleteDateName(): ?string;

    /**
     * Check if model was soft deleted
     * 
     * @return bool
     */
    public function isTrashed(): bool;

    /**
     * Count total number of model items.
     *
     * @param array $params Custom query params
     * @return integer
     */
    public function count(string|null $query = null, $params = array()): int;

    /**
     * Find model for id. Without trashed (deleted) models
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public function find($id, $conditions = [], $params = [], $columns = []): ?self;

    /**
     * Find model for id. With trashed (deleted) models
     *
     * @param mixed $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public function findTrashed($id, $conditions = [], $params = [], $columns = []): ?self;

    /**
     * Find model with condition.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return self|null
     */
    public function findWhere($conditions = [], $params = [], $columns = []): ?self;

    /**
     * Get list of model. Without trashed (deleted) models
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names. 
     * @return self[]
     */
    public function all($conditions = [], $params = [], $columns = []): array;

    /**
     * Get list of model. With trashed (deleted) models
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names. 
     * @return self[]
     */
    public function allTrashed($conditions = [], $params = [], $columns = []): array;

    /**
     * Delete model. 
     *
     * @param bool $force Force permanent delete or soft delete if supported
     * @return bool
     */
    public function delete($force = false): bool;

    /**
     * Restore model
     * @return bool
     */
    public function restore(): bool;

    /**
     * Save model
     * 
     * @param bool $trim Exclude NULL properties before saving
     * @param bool $relations Save relations if available
     * @return bool
     */
    public function save($trim = false, $relations = true): bool;
}
