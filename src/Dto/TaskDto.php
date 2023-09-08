<?php

namespace Armie\Dto;

use Armie\Tasks\Task;

use function Armie\Helpers\serialize;
use function Armie\Helpers\unserialize;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class TaskDto
{
    public string $name;
    public bool $async;
    public string|null $class = null;
    public array $params = [];

    public function __construct()
    {
        $this->name = Task::class . ':' . microtime(true) . ':' . bin2hex(random_bytes(8));
        $this->async = true;
    }

    /**
     * Set the value of name.
     *
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the value of async.
     *
     * @return self
     */
    public function setAsync($async)
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Set the value of class. Subclass of `Task`.
     *
     * @return self
     */
    public function setClass(string|null $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Set the value of params.
     *
     * @return self
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Gets a string representation of the object.
     *
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return base64_encode(serialize($this));
    }

    /**
     * Parse request.
     *
     * @param string $payload
     * @return ?self
     */
    public static function parse(string $payload): ?self
    {
        $dto = unserialize(base64_decode($payload) ?: null);
        if ($dto && $dto instanceof self) {
            return $dto;
        }

        return null;
    }
}
