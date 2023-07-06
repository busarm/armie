<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Helpers\Security;
use Busarm\PhpMini\Interfaces\StorageBagInterface;

/**
 * Store simple plain data (string, array, object) as file
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class FileStore implements StorageBagInterface
{
	const STORAGE_EXT = '.store';
	protected array $original = [];

	/**
	 * @param string $basePath Storage root folder
	 */
	public function __construct(private string $basePath)
	{
	}

	/**
	 * Load list of data into store
	 * 
	 * @param array<string,array|object|string> $data
	 * @return self
	 */
	public function load(array $data): self
	{
		foreach ($data as $path => $item) {
			if ($this->set($path, $item)) {
				$this->original[$path] = md5(json_encode($item));
			}
		}
		return $this;
	}

	/**
	 * Set file
	 *
	 * @param string $path
	 * @param mixed $data
	 * @param bool $sanitize
	 * @return bool
	 */
	public function set(string $path, mixed $data, $sanitize = true): bool
	{
		if (is_string($data) || is_array($data) || is_object($data)) {
			if ($data && !is_null($serialized = \serialize(
				$sanitize ? Security::clean($data) : $data
			))) {
				$data = $serialized;
			}

			$path = $this->fullPath($path);
			$dir = \dirname($path);

			// Directory not available
			if (!\is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			return \file_put_contents($path, $data);
		}
		return false;
	}

	/**
	 * 
	 * Checks if an file exists
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function has(string $path): bool
	{
		return \is_file($this->fullPath($path));
	}

	/**
	 * Get file
	 *
	 * @param string $path
	 * @param mixed $default
	 * @param bool $sanitize
	 * @return mixed
	 */
	public function get(string $path, $default = null, $sanitize = false): mixed
	{
		if ($this->has($path)) {
			$data = \file_get_contents($this->fullPath($path));
			if ($data && !is_null($parsed = \unserialize($data))) {
				$data = $parsed;
			}
			return $sanitize ? Security::clean($data) : $data;
		}
		return $default;
	}

	/**
	 * Pull file: Get and delete
	 *
	 * @param string $path
	 * @param mixed $default
	 * @param bool $sanitize
	 * @return mixed
	 */
	public function pull(string $path, $default = null, $sanitize = false): mixed
	{
		$value = $this->get($path, $default, $sanitize);
		$this->remove($path);
		return $value;
	}

	/**
	 * Get all files
	 *
	 * @return array
	 */
	public function all(): array
	{
		$list = [];
		foreach ($this->listFiles($this->basePath) as $path) {
			$list[$path] = $this->get($path);
		}
		return $list;
	}

	/**
	 * Get updated files
	 *
	 * @return array
	 */
	public function updates(): array
	{
		$list = [];
		foreach ($this->all() as $path => $item) {
			if (
				!isset($this->original[$path])
				|| $this->original[$path] != md5(json_encode($item))
			) {
				$list[$path] = $item;
			}
		}
		return $list;
	}

	/**
	 * Replace files in store with given list
	 *
	 * @param array<string,array|object|string> $data
	 * @return void
	 */
	public function replace(array $data)
	{
		$this->clear();
		$this->load($data);
	}

	/**
	 * Remove file
	 *
	 * @param string $path
	 * @return void
	 */
	public function remove(string $path)
	{
		if ($this->has($path)) {
			\unlink($this->fullPath($path));
		}
	}

	/**
	 * Remove all file
	 *
	 * @return void
	 */
	public function clear()
	{
		foreach ($this->listFiles($this->basePath) as $path) {
			\unlink($path);
		}
	}

	/**
	 * 
	 * Get full path
	 *
	 * @param string $path
	 * @return string
	 */
	private function fullPath(string $path): string
	{
		$path = $this->isStorePath($path) ?
			sha1(str_ireplace(self::STORAGE_EXT, '', $path)) . self::STORAGE_EXT :
			sha1($path) . self::STORAGE_EXT;
		return (str_starts_with($path, $this->basePath) ? $path : $this->basePath . DIRECTORY_SEPARATOR . $path);
	}

	/**
	 * 
	 * Path is a valid store path
	 *
	 * @param string $path
	 * @return bool
	 */
	private function isStorePath(string $path): bool
	{
		return str_ends_with($path, self::STORAGE_EXT);
	}

	/**
	 * Lst all files in directory
	 *
	 * @param string $dir
	 * @return array
	 */
	private function listFiles(string $dir): array
	{
		$list = [];
		if (\is_dir($dir)) {
			$items = array_diff(\scandir($dir), ['.', '..']);
			foreach ($items as $item) {
				if ($this->isStorePath($item)) {
					$item = $dir . DIRECTORY_SEPARATOR . $item;
					if (is_dir($item)) {
						$list = array_merge($list, $this->listFiles($item));
					} else {
						$list[] = $item;
					}
				}
			}
		}
		return $list;
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
