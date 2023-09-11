<?php

namespace Armie\Bags;

use Armie\Async;
use Armie\Data\PDO\Field;
use Armie\Data\PDO\Model;
use Armie\Data\PDO\Models\BaseModel;
use Armie\Enums\DataType;
use Armie\Helpers\Security;
use Armie\Interfaces\StorageBagInterface;
use BadMethodCallException;
use Generator;

use function Armie\Helpers\serialize;
use function Armie\Helpers\unserialize;

/**
 * Store key-value data in database table.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @inheritDoc
 */
class DatabaseStore implements StorageBagInterface
{
    const STORAGE_SUFFIX = '.astore';

    /**
     * Last time store was loaded.
     *
     * @var int
     */
    protected int $loadTime = 0;

    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @param string $tableName       Model table name
     * @param string $keyColumn       Model `key` column name. Must be a `VAR_CHAR`(80 to 100) column and PRIMARY KEY
     * @param string $valueColumn     Model `value` column name. Must be a `MEDIUMTEXT` or `LONGTEXT` column
     * @param string $typeColumn      Model `type` column name. Must be a `VAR_CHAR`(32) column. Suggestion: INDEX this column
     * @param string $createdAtColumn Model `createdAt` column name. Must be a `DATETIME` column
     * @param string $type            Store type. E.g queue, cache, session. Default: `store`
     * @param bool   $async           Save asynchronously. Default: false
     */
    public function __construct(
        string $tableName,
        string $keyColumn = 'key',
        protected string $valueColumn = 'value',
        protected string $typeColumn = 'type',
        string $createdAtColumn = 'createdAt',
        protected string $type = 'store',
        protected bool $async = false
    ) {
        $this->model = BaseModel::init(
            tableName: $tableName,
            keyName: $keyColumn,
            createdDateName: $createdAtColumn,
            fields: [
                new Field($keyColumn, DataType::STRING),
                new Field($this->valueColumn, DataType::STRING),
                new Field($this->typeColumn, DataType::STRING),
                new Field($createdAtColumn, DataType::DATETIME),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function load(array $data): self
    {
        foreach ($data as $path => $item) {
            if ($this->set($path, $item)) {
                $this->loadTime = time();
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->model->find($key, [$this->typeColumn => $this->type]) ? true : null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $data, $sanitize = true): bool
    {
        if (strlen($key) > 100) {
            throw new BadMethodCallException('Length of `key` must be less than or equals to 100');
        }

        // 1. Sanitize
        $data = $sanitize ? Security::clean($data) : $data;
        // 2. Serialize
        if ($data && !empty($serialized = serialize($data))) {
            $data = $serialized;
        }
        // 3. Save
        if ($this->async) {
            return (bool) Async::runTask(function () use ($key, $data) {
                $model = $this->model->clone();
                $model->load([
                    $this->model->getKeyName() => $this->fullKey($key),
                    $this->valueColumn         => $data,
                    $this->typeColumn          => $this->type,
                ]);

                return $model->save(true, false);
            });
        } else {
            $model = $this->model->clone();
            $model->load([
                $this->model->getKeyName() => $this->fullKey($key),
                $this->valueColumn         => $data,
                $this->typeColumn          => $this->type,
            ]);

            return $model->save(true, false);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null, $sanitize = false): mixed
    {
        // 1. Fetch
        $item = $this->model->find($this->fullKey($key), [$this->typeColumn => $this->type]);

        if ($item && !empty($data = $item->get($this->valueColumn))) {
            // 2. Unserialize
            if (!is_null($parsed = unserialize($data))) {
                $data = $parsed;
            }
            // 3. Sanitize
            $data = $sanitize ? Security::clean($data) : $data;

            return $data;
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function pull(string $key, $default = null, $sanitize = false): mixed
    {
        // 1. Fetch
        $item = $this->model->find($this->fullKey($key), [$this->typeColumn => $this->type]);

        if ($item && !empty($data = $item->get($this->valueColumn))) {
            // 2. Unserialize
            if (!is_null($parsed = unserialize($data))) {
                $data = $parsed;
            }
            // 3. Sanitize
            $data = $sanitize ? Security::clean($data) : $data;

            // 4. Delete
            if ($this->async) {
                Async::runTask(fn () => $item->delete(true));
            } else {
                $item->delete(true);
            }

            return $data;
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $list = [];
        foreach ($this->model->all([$this->typeColumn => $this->type]) as $item) {
            // 1. Fetch
            $key = $item->get($this->model->getKeyName());
            $data = $item->get($this->valueColumn);

            // 2. Unserialize
            if ($data && !is_null($parsed = unserialize($data))) {
                $data = $parsed;
            }

            $list[$key] = $data;
        }

        return $list;
    }

    /**
     * @inheritDoc
     */
    public function updates(): array
    {
        $list = [];
        foreach ($this->model->all([$this->typeColumn => $this->type]) as $item) {
            if (strtotime($item->get($this->model->getCreatedDateName())) > $this->loadTime) {
                // 1. Fetch
                $key = $item->get($this->model->getKeyName());
                $data = $item->get($this->valueColumn);

                // 2. Unserialize
                if ($data && !is_null($parsed = unserialize($data))) {
                    $data = $parsed;
                }

                $list[$key] = $data;
            }
        }

        return $list;
    }

    /**
     * @inheritDoc
     */
    public function itterate(bool $delete = false): Generator
    {
        foreach ($this->model->itterate([$this->typeColumn => $this->type]) as $item) {
            // 1. Fetch
            $key = $item->get($this->model->getKeyName());
            $data = $item->get($this->valueColumn);

            // 2. Unserialize
            if ($data && !is_null($parsed = unserialize($data))) {
                $data = $parsed;
            }

            // 3. Delete (if required)
            if ($delete) {
                if ($this->async) {
                    Async::runTask(fn () => $item->delete(true));
                } else {
                    $item->delete(true);
                }
            }

            yield $key => $data;
        }
    }

    /**
     * @inheritDoc
     */
    public function replace(array $data)
    {
        $this->clear();
        $this->load($data);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key)
    {
        if ($this->async) {
            Async::runTask(function () use ($key) {
                $item = $this->model->find($this->fullKey($key), [$this->typeColumn => $this->type]);
                if ($item) {
                    $item->delete(true);
                }
            });
        } else {
            $item = $this->model->find($this->fullKey($key), [$this->typeColumn => $this->type]);
            if ($item) {
                $item->delete(true);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        foreach ($this->model->all() as $item) {
            if ($this->async) {
                Async::runTask(function () use ($item) {
                    $item->delete(true);
                });
            } else {
                $item->delete(true);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Get full key.
     *
     * @param string $key
     *
     * @return string
     */
    private function fullKey(string $key): string
    {
        return $this->isStoreKey($key) ? $key : sha1($key).self::STORAGE_SUFFIX;
    }

    /**
     * Key is a valid store key.
     *
     * @param string $path
     *
     * @return bool
     */
    private function isStoreKey(string $path): bool
    {
        return str_ends_with($path, self::STORAGE_SUFFIX);
    }

    /**
     * Gets a string representation of the object.
     *
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return json_encode($this->all());
    }
}
