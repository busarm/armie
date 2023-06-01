<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Helpers\Security;
use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Closure;

use function Busarm\PhpMini\Helpers\log_info;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Attribute implements StorageBagInterface
{

	protected Closure|null $onChange = null;
	protected Closure|null $onDelete = null;
	protected array $keys = [];

	public function __construct(protected array $attributes = [])
	{
		$this->keys = array_combine(array_keys(array_change_key_case($this->attributes)), array_keys($this->attributes));
	}

	/**
	 * Mirror attributes with external source
	 *
	 * @return self
	 */
	public function mirror(&$attributes): self
	{
		$this->attributes = $attributes;
		$this->keys = array_combine(array_keys(array_change_key_case($this->attributes)), array_keys($this->attributes));
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
		$this->keys[strtolower($name)] = $name;
		$this->attributes[$name] = $value;
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
		return array_key_exists($this->key($name), $this->attributes);
	}

	/**
	 * Get key exact key for name
	 *
	 * @param string $name
	 * @return string
	 */
	public function key(string $name): string
	{
		return $this->keys[strtolower($name)] ?? $name;
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
	 * Set bulk attributes
	 *
	 * @param array $data
	 * @return void
	 */
	public function replace(array $data)
	{
		$this->attributes = array_merge($this->attributes, $data);
		$this->keys = array_combine(array_keys(array_change_key_case($this->attributes)), array_keys($this->attributes));
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
	}

	/**
	 * Gets a string representation of the object
	 *
	 * @return string Returns the `string` representation of the object.
	 */
	public function __toString()
	{
		return json_encode($this->attributes);
	}
}
