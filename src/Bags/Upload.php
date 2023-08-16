<?php

namespace Armie\Bags;

use Armie\Interfaces\UploadBagInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @link https://github.com/josantonius/php-session
 */
final class Upload implements UploadBagInterface
{

	/**
	 * @param \Psr\Http\Message\UploadedFileInterface[] $uploads
	 */
	public function __construct(protected array $uploads = [])
	{
	}

	/**
	 * Set uploaded file
	 *
	 * @param string $name
	 * @param \Psr\Http\Message\UploadedFileInterface $value
	 * @param mixed $options
	 * @return bool
	 */
	public function set(string $name, \Psr\Http\Message\UploadedFileInterface $value, $options = NULL): bool
	{
		$this->uploads[$name] = $value;
		return true;
	}

	/**
	 * Checks if an uploaded file exists
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has(string $name): bool
	{
		return isset($this->uploads[$name]);
	}

	/**
	 * Get uploaded file
	 *
	 * @param string $name
	 * @param \Psr\Http\Message\UploadedFileInterface|null $default
	 * @return \Psr\Http\Message\UploadedFileInterface
	 */
	public function get(string $name, \Psr\Http\Message\UploadedFileInterface|null $default = null): \Psr\Http\Message\UploadedFileInterface
	{
		return $this->has($name) ? $this->uploads[$name] : $default;
	}

	/**
	 * Pull uploaded file: Get and delete
	 *
	 * @param string $name
	 * @param \Psr\Http\Message\UploadedFileInterface|null $default
	 * @return \Psr\Http\Message\UploadedFileInterface
	 */
	public function pull(string $name, \Psr\Http\Message\UploadedFileInterface|null $default = null): \Psr\Http\Message\UploadedFileInterface
	{
		$value = $this->get($name, $default);
		$this->remove($name);
		return $value;
	}

	/**
	 * Get all uploads
	 *
	 * @return array
	 */
	public function all(): array
	{
		return $this->uploads;
	}

	/**
	 * Set bulk uploaded files
	 *
	 * @param \Psr\Http\Message\UploadedFileInterface[] $data
	 * @return void
	 */
	public function replace(array  $data)
	{
		$this->uploads = array_merge($this->uploads, $data);
	}

	/**
	 * Remove uploaded file
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function remove(string $name)
	{
		if ($this->has($name)) unset($this->uploads[$name]);
	}

	/**
	 * Remove all uploaded files
	 *
	 * @return void
	 */
	public function clear()
	{
		$this->uploads = [];
	}

	/**
	 * Gets a string representation of the object
	 *
	 * @return string Returns the `string` representation of the object.
	 */
	public function __toString()
	{
		return json_encode($this->uploads);
	}
}
