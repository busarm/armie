<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Helpers\Security;
use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Closure;
use Opis\Closure\SerializableClosure;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Bag implements StorageBagInterface
{
	protected SerializableClosure|Closure|null $onChange = null;
	protected SerializableClosure|Closure|null $onDelete = null;
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
	 * Mirror attributes with external source
	 *
	 * @param array $attributes
	 * @return self
	 */
	public function mirror(&$attributes): self
	{
		$this->attributes = &$attributes;
		$this->original = $this->attributes;
		$this->keys = [];
		foreach (array_keys($this->attributes) as $name) {
			$this->key($name);
		}
		return $this;
	}

	/**
	 * Load attributes
	 * 
	 * @param array $attributes
	 * @return self
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
	 * Set on attribute change listner
	 * 
	 * @return self
	 */
	public function onChange(Closure $onChange): self
	{
		$this->onChange = $onChange;
		return $this;
	}

	/**
	 * Set on attribute delete listner
	 * 
	 * @return self
	 */
	public function onDelete(Closure $onDelete): self
	{
		$this->onDelete = $onDelete;
		return $this;
	}

	/**
	 * Set attribute
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param mixed $options
	 * @return bool
	 */
	public function set(string $name, mixed $value, $options = NULL): bool
	{
		$this->attributes[$this->key($name)] = $value;
		if ($this->onChange) ($this->onChange)($name, $value);
		return true;
	}

	/**
	 * Checks if an attribute exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public function has(string $name): bool
	{
		return array_key_exists($this->key($name, true), $this->attributes);
	}

	/**
	 * Get key exact key for name or set of it doesn't exist
	 *
	 * @param string $name Name of key
	 * @param bool $force Force retrieve key. Don't set if no available
	 * @return string
	 */
	public function key(string $name, $force = false): string
	{
		$index = str_replace('-', '_', strtolower($name));
		$key = $this->keys[$index] ?? NULL;
		if (!$key && !$force) {
			$key = $this->keys[$index] = $name;
		}
		return $key ?? $name;
	}

	/**
	 * Get attribute
	 *
	 * @param string $name
	 * @param mixed $default
	 * @param bool $sanitize
	 * @return mixed
	 */
	public function get(string $name, $default = null, $sanitize = false): mixed
	{
		$value = $this->attributes[$this->key($name)] ??  null;
		return isset($value) ?
			($sanitize ? Security::clean($value) : $value) :
			$default;
	}

	/**
	 * Pull attribute: Get and delete
	 *
	 * @param string $name
	 * @param mixed $default
	 * @param bool $sanitize
	 * @return mixed
	 */
	public function pull(string $name, $default = null, $sanitize = false): mixed
	{
		$value = $this->get($name, $default, $sanitize);
		$this->remove($name);
		return $value;
	}

	/**
	 * Get all attributes
	 *
	 * @return array
	 */
	public function all(): array
	{
		return $this->attributes;
	}

	/**
	 * Get updated attributes
	 *
	 * @return array
	 */
	public function updates(): array
	{
		return array_diff($this->attributes, $this->original);
	}

	/**
	 * Set bulk attributes
	 *
	 * @param array $data
	 * @return void
	 */
	public function replace(array $data)
	{
		$this->attributes = array_merge($this->attributes, $data);
		foreach (array_keys($this->attributes) as $name) {
			$this->key($name);
		}

		if ($this->onChange) foreach ($data as $name => $value) ($this->onChange)($name, $value);
	}

	/**
	 * Remove attribute
	 *
	 * @param string $name
	 * @return void
	 */
	public function remove(string $name)
	{
		if ($this->has($name)) {
			unset($this->attributes[$this->key($name)]);
			unset($this->keys[strtolower($name)]);
		};
		if ($this->onDelete) ($this->onDelete)($name);
	}

	/**
	 * Remove all attribute
	 *
	 * @return void
	 */
	public function clear()
	{
		$data = $this->attributes;
		$this->attributes = [];
		$this->keys = [];

		if ($this->onDelete) foreach (array_keys($data) as $name) ($this->onDelete)($name);
		$data = NULL;
	}

	/**
	 * Number of items in store
	 *
	 * @return int
	 */
	public function count(): int
	{
		return count($this->all());
	}

	/**
	 * Gets a string representation of the object
	 *
	 * @return string Returns the `string` representation of the object.
	 */
	public function __toString()
	{
		return json_encode($this->all());
	}
}
