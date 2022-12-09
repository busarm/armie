<?php

namespace Busarm\PhpMini\Data\PDO;

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
     * @param Model $model Reference Model
     * @param array $keys Reference keys e.g. `['fromModelKey1' => 'toModelKey1', 'modelKey2' => 'fromModelKey2']`
     * @param string|null $name Reference keys. Default = `$model` class name
     */
    public function __construct(private Model $model, private array $keys, private string|null $name = null)
    {
        $this->name = $name ?? get_class($model);
    }

    /**
     * Get reference 
     * 
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
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
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
