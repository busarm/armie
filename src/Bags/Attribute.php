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
     * @param mixed $options
     *
     * @return bool
     */
    public function set(string $name, mixed $value, $options = NULL): bool
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
	public function has(string $name): bool
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
	public function get(string $name, $default = null): mixed
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
	public function all(): array
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
	public function replace(array $data)
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
	public function remove(string $name)
	{
		if ($this->has($name)) unset($this->attributes[$name]);
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
		if ($this->onDelete) foreach (array_keys($data) as $name) ($this->onDelete)($name);
	}
}
