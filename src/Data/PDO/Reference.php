<?php

namespace Busarm\PhpMini\Data\PDO;

use Exception;
use Stringable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Reference implements Stringable
{
    /**
     * @param Model|class-string<Model> $model Reference Model or Model Class Name
     * @param array $keys Reference keys e.g. `['fromModelKey1' => 'toModelKey1', 'fromModelKey2' => 'toModelKey2']`
     */
    public function __construct(private Model|string $model, private array $keys)
    {
        if (!($model instanceof Model) && !is_subclass_of($model, Model::class)) {
            throw new Exception('Reference `model` must be a subclass of ' . Model::class);
        }
    }

    /**
     * Get reference 
     * 
     * @param Connection|null $db
     * @return Model
     */
    public function getModel(Connection|null $db = null): Model
    {
        return $this->model instanceof Model ?
            $this->model :
            $this->model = (new \ReflectionClass($this->model))->newInstance($db);
    }

    /**
     * Get reference keys
     * 
     * @return array
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * Get reference name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->model instanceof Model ? get_class($this->model) : $this->model;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
