<?php

namespace Busarm\PhpMini\Data\PDO;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\PaginatedCollectionDto;
use Busarm\PhpMini\Interfaces\Crud\CrudRepositoryInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Repository implements CrudRepositoryInterface
{
    public function __construct(private Model $model)
    {
    }

    /**
     * Count number of rows in query
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @return int
     */
    public function count(string $query, $params = array()): int
    {
        return $this->model->count($query, $params);
    }

    /**
     * Execute query.
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @return int|bool Returns row count for modification query or boolean success status
     */
    public function query(string $query, array $params = array()): int|bool
    {
        if (!empty($query)) {
            $stmt = $this->model->getDb()->prepare($query);
            if ($stmt && $stmt->execute($params)) {
                $isEdit = $this->model->getDb()->matchInsertQuery($query) ||
                    $this->model->getDb()->matchUpdateQuery($query) ||
                    $this->model->getDb()->matchDeleteQuery($query);
                return $isEdit ? $stmt->rowCount() : true;
            }
        }
        return false;
    }

    /**
     * Find single model with query.
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @return BaseDto|null
     */
    public function querySingle(string $query, $params = array()): ?BaseDto
    {
        if (!empty($query) && $this->model->getDb()->matchSelectQuery($query)) {
            $stmt = $this->model->getDb()->prepare($query);
            if ($stmt && $stmt->execute($params) && ($result = $stmt->fetch(Connection::FETCH_ASSOC))) {
                return BaseDto::with($result);
            }
        }
        return null;
    }

    /**
     * Find list of models with query.
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @return CollectionBaseDto
     */
    public function queryList(string $query, $params = array()): CollectionBaseDto
    {
        if (!empty($query) && $this->model->getDb()->matchSelectQuery($query)) {
            $stmt = $this->model->getDb()->prepare($query);
            if ($stmt && $stmt->execute($params) && ($result = $stmt->fetchAll(Connection::FETCH_ASSOC))) {
                return CollectionBaseDto::of($result);
            }
        }
        return CollectionBaseDto::of([]);
    }

    /**
     * Find list of models with paginated query.
     *
     * @param string $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param int $page Page Number Default: 1
     * @param int $limit Page Limit. Default: 0 to disable
     * @return PaginatedCollectionDto
     */
    public function queryPaginate(string $query, $params = array(), int $page = 1, int $limit = 0): PaginatedCollectionDto
    {
        if (empty($query) && $this->model->getDb()->matchSelectQuery($query)) return new PaginatedCollectionDto;

        $limit = $limit > 0 ? $limit : $this->model->getPerPage();
        $total = $this->count($query, $params);
        $data = $this->queryList($this->model->getDb()->applyLimit($query, $page, $limit), $params);
        return (new PaginatedCollectionDto($data, $page, $limit, $total, $data->count()));
    }

    /**
     * Get all models. Without trashed (deleted) models
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return CollectionBaseDto
     */
    public function all(array $conditions = array(), array $params = array(), array $columns = array()): CollectionBaseDto
    {
        return CollectionBaseDto::of($this->model->all($conditions, $params, $columns));
    }

    /**
     * Get all models. With trashed (deleted) models.
     *
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return CollectionBaseDto
     */
    public function allTrashed(array $conditions = array(), array $params = array(), array $columns = array()): CollectionBaseDto
    {
        if (!empty($this->model->getSoftDeleteDateName())) {
            return CollectionBaseDto::of($this->model->allTrashed($conditions, $params, $columns));
        }
        return $this->all($conditions, $params, $columns);
    }

    /**
     * Get paginated list of models.
     *
     * @param int $page Page Number Default: 1
     * @param int $limit Page Limit. Default: 0 to disable
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']`  or `['id' => ':id']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params `[$id]` or [':id' => $id] 
     * @param array $columns Select Colomn names. 
     * @return PaginatedCollectionDto
     */
    public function paginate(int $page = 1, int $limit = 0, array $conditions = array(), array $params = array(), array $columns = array()): PaginatedCollectionDto
    {
        if (empty($columns)) $columns = ["*"];

        $colsPlaceHolders = $this->model->parseColumns($columns);
        $condPlaceHolders = $this->model->parseConditions($conditions);

        $query = sprintf(
            "SELECT %s FROM %s %s",
            $colsPlaceHolders,
            $this->model->getTableName(),
            !empty($condPlaceHolders) ? 'WHERE ' . $condPlaceHolders : ''
        );

        $limit = $limit > 0 ? $limit : $this->model->getPerPage();
        $total = $this->count($query, $params);
        $data = $this->queryList($this->model->getDb()->applyLimit($query, $page, $limit), $params);
        return (new PaginatedCollectionDto($data, $page, $limit, $total, $data->count()));
    }

    /**
     * Find model by id. Without trashed (deleted) models
     *
     * @param int|string $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names
     * @return BaseDto|null
     */
    public function findById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto
    {
        return $this->model->find($id, $conditions, $params, $columns);
    }

    /**
     * Find model by id. With trashed (deleted) models
     *
     * @param int|string $id
     * @param array $conditions Query Conditions. e.g `createdAt < now()` or `['id' => 1]` or `['id' => '?']` or `['id' => [1,2,3]]`
     * @param array $params Query Params. e.g SQL query params
     * @param array $columns Select Colomn names
     * @return BaseDto|null
     */
    public function findTrashedById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto
    {
        if (!empty($this->model->getSoftDeleteDateName())) {
            return $this->model->findTrashed($id, $conditions, $params, $columns);
        }
        return $this->findById($id, $conditions, $params, $columns);
    }

    /**
     * Create a model.
     *
     * @param array $data
     * @return BaseDto|null
     */
    public function create(array $data): ?BaseDto
    {
        $model = $this->model->clone();
        $model->load($data);
        if ($model->save()) {
            return $model;
        }
        return null;
    }

    /**
     * Create list of models.
     *
     * @param array $data
     * @return bool
     */
    public function createBulk(array $data): bool
    {
        return $this->model->transaction(function () use ($data) {
            foreach ($data as $item) {
                if (!$this->create($item)) {
                    $this->model->getDb()->rollBack();
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Update model by id.
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function updateById(int|string $id, array $data): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            $model->load($data);
            return $model->save();
        }
        return false;
    }

    /**
     * Update list of models.
     *
     * @param array $data
     * @return bool
     */
    public function updateBulk(array $data): bool
    {
        return $this->model->transaction(function () use ($data) {
            foreach ($data as $item) {
                if (!isset($item[$this->model->getKeyName()]) || !$this->updateById($item[$this->model->getKeyName()], $item)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Delete model by id.
     *
     * @param int|string $id
     * @param bool $force Permanently delete
     * @return bool
     */
    public function deleteById(int|string $id, $force = false): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            return $model->delete($force);
        }
        return false;
    }

    /**
     * Delete list of models.
     *
     * @param array $ids
     * @param bool $force Permanently delete
     * @return bool
     */
    public function deleteBulk(array $ids, $force = false): bool
    {
        return $this->model->transaction(function () use ($ids, $force) {
            foreach ($ids as $id) {
                if (!$this->deleteById($id, $force)) {
                    $this->model->getDb()->rollBack();
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Restore model by id.
     *
     * @param int|string $id
     * @return bool
     */
    public function restoreById(int|string $id): bool
    {
        $model = $this->model->findTrashed($id);
        if ($model) {
            return $model->restore();
        }
        return false;
    }

    /**
     * Restore list of models.
     *
     * @param array $ids
     * @return bool
     */
    public function restoreBulk(array $ids): bool
    {
        $this->model->getDb()->beginTransaction();
        foreach ($ids as $id) {
            if (!$this->restoreById($id)) {
                $this->model->getDb()->rollBack();
                return false;
            }
        }
        return $this->model->getDb()->commit();
    }
}
