<?php

namespace Busarm\PhpMini\Dto;

use function Opis\Closure\serialize;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class TaskDto
{
    public string $name;
    public bool $async = true;
    public string|null $key = null;
    public string|null $class = null;
    public array $params = [];

    public function __construct()
    {
        $this->name = 'Task|' . microtime(true);
    }

    /**
     * Gets a string representation of the object
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return serialize($this);
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the value of async
     *
     * @return  self
     */
    public function setAsync($async)
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Set the value of key
     *
     * @return  self
     */
    public function setKey(string|null $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Set the value of class. Subclass of `Task`
     *
     * @return  self
     */
    public function setClass(string|null $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Set the value of params
     *
     * @return  self
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }
}
