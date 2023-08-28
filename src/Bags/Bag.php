<?php

namespace Armie\Bags;

use Armie\Helpers\Security;
use Armie\Interfaces\StorageBagInterface;

/**
 * Memory (array) store.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @inheritDoc
 */
class Bag implements StorageBagInterface
{
    protected array $keys = [];
    protected array $original = [];

    public function __construct(protected array $attributes = [])
    {
        $this->original = $this->attributes;
        foreach (array_keys($this->attributes) as $name) {
            $this->key($name);
        }
    }

    /**
     * @inheritDoc
     */
    public function load(array $attributes): self
    {
        $this->attributes = $attributes;
        $this->original = $this->attributes;
        $this->keys = [];
        foreach (array_keys($this->attributes) as $name) {
            $this->key($name);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, mixed $value, $options = null): bool
    {
        $this->attributes[$this->key($name)] = $value;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return array_key_exists($this->key($name, true), $this->attributes);
    }

    /**
     * Get or Set original key.
     *
     * @param string $name
     * @param bool   $force Force get, don't set if empty
     *
     * @return string
     */
    public function key(string $name, $force = false): string
    {
        $index = str_replace('-', '_', strtolower($name));
        $key = $this->keys[$index] ?? null;
        if (!$key && !$force) {
            $key = $this->keys[$index] = $name;
        }

        return $key ?? $name;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, $default = null, $sanitize = false): mixed
    {
        $value = $this->attributes[$this->key($name)] ?? null;

        return isset($value) ?
            ($sanitize ? Security::clean($value) : $value) :
            $default;
    }

    /**
     * @inheritDoc
     */
    public function pull(string $name, $default = null, $sanitize = false): mixed
    {
        $value = $this->get($name, $default, $sanitize);
        $this->remove($name);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function updates(): array
    {
        return array_diff($this->attributes, $this->original);
    }

    /**
     * @inheritDoc
     */
    public function replace(array $data)
    {
        $this->attributes = array_merge($this->attributes, $data);
        foreach (array_keys($this->attributes) as $name) {
            $this->key($name);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(string $name)
    {
        if ($this->has($name)) {
            unset($this->attributes[$this->key($name)]);
            unset($this->keys[strtolower($name)]);
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->attributes = [];
        $this->keys = [];
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->all());
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
