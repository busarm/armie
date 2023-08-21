<?php

namespace Armie\Data\PDO;

use Armie\Data\PDO\Model;
use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Dto\PaginatedCollectionDto;
use Armie\Interfaces\Data\QueryRepositoryInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @template T
 */
class Repository implements QueryRepositoryInterface
{
    /**
     *
     * @param Model&T $model
     */
    public function __construct(private Model $model)
    {
    }

    /**
     * Count number of rows or rows in query
     *
     * @param string|null $query Model Provider Query. e.g SQL query
     * @param array $params Query Params. e.g SQL query params
     * @return int
     */
    public function count(string|null $query = null, $params = array()): int
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
        return $this->model->getDatabase()->executeQuery($query, $params);
    }

    /**
     * @inheritDoc
     */
    public function querySingle(string $query, $params = array()): ?BaseDto
    {
        if (!empty($query) && $this->model->getDatabase()->matchSelectQuery($query)) {
            $stmt = $this->model->getDatabase()->prepare($query);
            if ($stmt && $stmt->execute($params) && ($result = $stmt->fetch(Connection::FETCH_ASSOC))) {
                return BaseDto::with($result);
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function queryList(string $query, $params = array(), int $limit = 0): CollectionBaseDto
    {
        $limit = $limit > 0 ? $limit : $this->model->getPerPage();
        $query = $this->model->getDatabase()->applyLimit($query, 1, $limit);

        if (!empty($query) && $this->model->getDatabase()->matchSelectQuery($query)) {
            $stmt = $this->model->getDatabase()->prepare($query);
            if ($stmt && $stmt->execute($params) && ($result = $stmt->fetchAll(Connection::FETCH_ASSOC))) {
                return CollectionBaseDto::of($result);
            }
        }

        return CollectionBaseDto::of([]);
    }

    /**
     * @inheritDoc
     */
    public function queryPaginate(string $query, $params = array(), int $page = 1, int $limit = 0): PaginatedCollectionDto
    {

        $limit = $limit > 0 ? $limit : $this->model->getPerPage();
        $total = $this->count($query, $params);
        $query = $this->model->getDatabase()->applyLimit($query, $page, $limit);

        if (!empty($query) && $this->model->getDatabase()->matchSelectQuery($query)) {
            $stmt = $this->model->getDatabase()->prepare($query);
            if ($stmt && $stmt->execute($params) && ($result = $stmt->fetchAll(Connection::FETCH_ASSOC))) {
                return (new PaginatedCollectionDto(CollectionBaseDto::of($result), $page, $limit, $total));
            }
        }

        return new PaginatedCollectionDto;
    }

    /**
     * @inheritDoc
     */
    public function all(array $conditions = array(), array $params = array(), array $columns = array(), int $limit = 0): CollectionBaseDto
    {
        return CollectionBaseDto::of($this->model->all($conditions, $params, $columns, $limit));
    }

    /**
     * @inheritDoc
     */
    public function allTrashed(array $conditions = array(), array $params = array(), array $columns = array(), int $limit = 0): CollectionBaseDto
    {
        if (!empty($this->model->getSoftDeleteDateName())) {
            return CollectionBaseDto::of($this->model->allTrashed($conditions, $params, $columns, $limit));
        }
        return $this->all($conditions, $params, $columns, $limit);
    }

    /**
     * @inheritDoc
     */
    public function paginate(array $conditions = array(), array $params = array(), array $columns = array(), int $page = 1, int $limit = 0): PaginatedCollectionDto
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

        return $this->queryPaginate($query, $params, $page, $limit);
    }

    /**
     * @inheritDoc
     */
    public function findById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto
    {
        $data = $this->model->find($id, $conditions, $params, $columns);
        return $data ? BaseDto::with($data->toArray()) : $data;
    }

    /**
     * @inheritDoc
     */
    public function findTrashedById(int|string $id, array $conditions = [], array $params = [], array $columns = ['*']): ?BaseDto
    {
        if (!empty($this->model->getSoftDeleteDateName())) {
            $data = $this->model->findTrashed($id, $conditions, $params, $columns);
            return $data ? BaseDto::with($data->toArray()) : $data;
        }
        return $this->findById($id, $conditions, $params, $columns);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): ?BaseDto
    {
        $model = $this->model->clone();
        $model->load($data);
        if ($model->save()) {
            return BaseDto::with($model);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function createBulk(array $data): bool
    {
        return $this->model->transaction(function () use ($data) {
            foreach ($data as $item) {
                if (!$this->create($item)) {
                    $this->model->getDatabase()->rollBack();
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function deleteBulk(array $ids, $force = false): bool
    {
        return $this->model->transaction(function () use ($ids, $force) {
            foreach ($ids as $id) {
                if (!$this->deleteById($id, $force)) {
                    $this->model->getDatabase()->rollBack();
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function restoreBulk(array $ids): bool
    {
        $this->model->getDatabase()->beginTransaction();
        foreach ($ids as $id) {
            if (!$this->restoreById($id)) {
                $this->model->getDatabase()->rollBack();
                return false;
            }
        }
        return $this->model->getDatabase()->commit();
    }
}
