<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Interfaces\Bags\AttributeBag;
use Closure;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @link https://github.com/josantonius/php-session
 */
class Attribute implements AttributeBag
{

	protected Closure|null $onChange = null;
	protected Closure|null $onDelete = null;

	public function __construct(protected array $attributes = [])
	{
	}

	/**
	 * Mirror attributes with external source
	 *
	 * @return self
	 */
	public function mirror(&$attributes): self
	{
		$this->attributes = $attributes;
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
	 *
	 * @return bool
	 */
	function set(string $name, mixed $value): bool
	{
		$this->attributes[$name] = $value;
		if ($this->onChange) ($this->onChange)($name, $value);
		return true;
	}

	/**
	 * Checks if an attribute exists
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	function has(string $name): bool
	{
		return isset($this->attributes[$name]);
	}

	/**
	 * Get attribute
	 *
	 * @param string $name
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	function get(string $name, $default = null): mixed
	{
		return $this->has($name) ? $this->attributes[$name] : $default;
	}

	/**
	 * Pull attribute: Get and delete
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function pull(string $name, $default = null): mixed
	{
		$value = $this->get($name, $default);
		$this->remove($name);
		return $value;
	}

	/**
	 * Get all attributes
	 *
	 * @return array
	 */
	function all(): array
	{
		return $this->attributes;
	}

	/**
	 * Set bulk attributes
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	function replace(array $data)
	{
		$this->attributes = array_merge($this->attributes, $data);
		if ($this->onChange) foreach ($data as $name => $value) ($this->onChange)($name, $value);
	}

	/**
	 * Remove attribute
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	function remove(string $name)
	{
		if ($this->has($name)) unset($this->attributes[$name]);
		if ($this->onDelete) ($this->onDelete)($name);
	}

	/**
	 * Remove all attribute
	 *
	 * @return void
	 */
	function clear()
	{
		$data = $this->attributes;
		$this->attributes = [];
		if ($this->onDelete) foreach (array_keys($data) as $name) ($this->onDelete)($name);
	}
}
