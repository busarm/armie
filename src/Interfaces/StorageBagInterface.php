<?php

namespace Busarm\PhpMini\Interfaces;

use Stringable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface StorageBagInterface extends Stringable
{
    /**
     * Set attribute
     *
     * @param string $name
     * @param mixed $value
     * @param mixed $options
     * @return bool
     */
    public function set(string $name, mixed $value, $options = NULL): bool;
    /**
     * 
     * Checks if an attribute exists
     *
     * @param string $name
     * @return boolean
     */
    public function has(string $name): bool;
    /**
     * Get attribute
     *
     * @param string $name
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function get(string $name, $default = null, $sanitize = false): mixed;
    /**
     * Pull attribute: Get and delete
     *
     * @param string $name
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function pull(string $name, $default = null, $sanitize = false): mixed;
    /**
     * Get all attributes
     *
     * @return array
     */
    public function all(): array;
    /**
     * Set bulk attributes
     *
     * @param array $data
     * @return void
     */
    public function replace(array $data);
    /**
     * Remove attribute
     *
     * @param string $name
     * @return void
     */
    public function remove(string $name);
    /**
     * Remove all attribute
     *
     * @return void
     */
    public function clear();
}
