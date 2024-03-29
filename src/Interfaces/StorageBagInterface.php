<?php

namespace Armie\Interfaces;

use Countable;
use Iterator;
use Stringable;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 *
 * @template T
 */
interface StorageBagInterface extends Stringable, Countable
{
    /**
     * Load attributes from external source.
     *
     * @param array<string, T> $attributes
     *
     * @return self
     */
    public function load(array $attributes): self;

    /**
     * Set attribute.
     *
     * @param string $name
     * @param T      $value
     * @param mixed  $options
     *
     * @return bool
     */
    public function set(string $name, $value, $options = null): bool;

    /**
     * Checks if an attribute exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get attribute.
     *
     * @param string $name
     * @param T      $default
     * @param bool   $sanitize
     *
     * @return T|null
     */
    public function get(string $name, $default = null, $sanitize = false): mixed;

    /**
     * Pull attribute: Get and delete.
     *
     * @param string $name
     * @param T      $default
     * @param bool   $sanitize
     *
     * @return T|null
     */
    public function pull(string $name, $default = null, $sanitize = false): mixed;

    /**
     * Get all attributes.
     *
     * @return array<string, T>
     */
    public function all(): array;

    /**
     * Get updated attributes.
     *
     * @return array<string, T>
     */
    public function updates(): array;

    /**
     * Set bulk attributes.
     *
     * @param array<string, T> $data
     *
     * @return void
     */
    public function replace(array $data);

    /**
     * Remove attribute.
     *
     * @param string $name
     *
     * @return void
     */
    public function remove(string $name);

    /**
     * Remove all attribute.
     *
     * @return void
     */
    public function clear();

    /**
     * Number of items in store.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Itterate store.
     *
     * @param bool $delete Delete item upon itteration
     *
     * @return Iterator<T>
     */
    public function itterate(bool $delete = false): Iterator;
}
