<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Helpers\Security;
use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Closure;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @inheritDoc
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
	public function set(string $name, mixed $value, $options = NULL): bool
	{
		$this->attributes[$this->key($name)] = $value;
		if ($this->onChange) call_user_func($this->onChange, $name, $value);
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
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	public function get(string $name, $default = null, $sanitize = false): mixed
	{
		$value = $this->attributes[$this->key($name)] ??  null;
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
	public function slice(int $offset, int $length): array
	{
		return array_slice($this->attributes, $offset, $length);
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

		if ($this->onChange)
			foreach ($data as $name => $value)
				call_user_func($this->onChange, $name, $value);
	}

	/**
	 * @inheritDoc
	 */
	public function remove(string $name)
	{
		if ($this->has($name)) {
			unset($this->attributes[$this->key($name)]);
			unset($this->keys[strtolower($name)]);
		};
		if ($this->onDelete) call_user_func($this->onDelete, $name);
	}

	/**
	 * @inheritDoc
	 */
	public function clear()
	{
		$data = $this->attributes;
		$this->attributes = [];
		$this->keys = [];

		if ($this->onDelete)
			foreach (array_keys($data) as $name)
				call_user_func($this->onDelete, $name);

		$data = NULL;
	}

	/**
	 * @inheritDoc
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
