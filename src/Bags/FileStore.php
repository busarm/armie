<?php

namespace Armie\Bags;

use Armie\Crypto;
use Armie\Helpers\Security;
use Armie\Interfaces\StorageBagInterface;
use Generator;

use function Armie\Helpers\async;
use function Armie\Helpers\serialize;
use function Armie\Helpers\unserialize;

/**
 * Store simple plain data (string, array, object) as file.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @inheritDoc
 */
class FileStore implements StorageBagInterface
{
    const STORAGE_EXT = '.astore';
    protected array $original = [];

    /**
     * @param string $basePath Storage root folder
     * @param string $key      Secret key for encryption. Ignore to disable encryption
     * @param bool   $async    Use async file store. Default: true
     */
    public function __construct(private string $basePath, private $key = null, private bool $async = true)
    {
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function has(string $path): bool
    {
        return \is_file($this->fullPath($path));
    }

    /**
     * @inheritDoc
     */
    public function set(string $path, mixed $data, $sanitize = true): bool
    {
        if (is_string($data) || is_array($data) || is_object($data)) {
            $path = $this->fullPath($path);
            $key = $this->key;

            $fn = function () use ($key, $path, $data, $sanitize) {
                $dir = \dirname($path);

                // Directory not available
                if (!\is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // 1. Sanitize
                $data = $sanitize ? Security::clean($data) : $data;
                // 2. Serialize
                if ($data && !is_null($serialized = serialize($data))) {
                    $data = $serialized;
                }
                // 3. Encrypt
                $data = !empty($key) ? Crypto::encrypt($data, $key) : $data;

                return \file_put_contents($path, $data);
            };

            if ($this->async) {
                async($fn);
            } else {
                return $fn();
            }

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function get(string $path, $default = null, $sanitize = false): mixed
    {
        if ($this->has($path)) {
            $path = $this->fullPath($path);

            $data = \file_get_contents($path);

            // 1. Decrypt
            $data = !empty($this->key) ? Crypto::decrypt($data, $this->key) : $data;
            // 2. Unserialize
            if ($data && !is_null($parsed = unserialize($data))) {
                $data = $parsed;
            }
            // 3. Sanitize
            $data = $sanitize ? Security::clean($data) : $data;

            return $data;
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function pull(string $path, $default = null, $sanitize = false): mixed
    {
        $value = $this->get($path, $default, $sanitize);
        $this->remove($path);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $list = [];
        foreach ($this->getFiles($this->basePath) as $path) {
            $list[$path] = $this->get($path);
        }

        return $list;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function replace(array $data)
    {
        $this->clear();
        $this->load($data);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $path)
    {
        $path = $this->fullPath($path);

        $fn = function () use ($path) {
            \unlink($path);
        };

        if ($this->has($path)) {
            if ($this->async) {
                async($fn);
            } else {
                $fn();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        foreach ($this->getFiles($this->basePath) as $path) {
            \unlink($path);
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return iterator_count($this->getFiles($this->basePath));
    }

    /**
     * Get full path.
     *
     * @param string $path
     *
     * @return string
     */
    private function fullPath(string $path): string
    {
        $path = $this->isStorePath($path) ? $path : sha1($path).self::STORAGE_EXT;

        return str_starts_with($path, $this->basePath) ? $path : $this->basePath.DIRECTORY_SEPARATOR.$path;
    }

    /**
     * Path is a valid store path.
     *
     * @param string $path
     *
     * @return bool
     */
    private function isStorePath(string $path): bool
    {
        return str_ends_with($path, self::STORAGE_EXT);
    }

    /**
     * Get list of files in directory.
     *
     * @param string $dir
     *
     * @return Generator<string>
     */
    private function getFiles(string $dir): Generator
    {
        if (is_dir($dir)) {
            foreach (new \DirectoryIterator($dir) as $path) {
                if (!$path->isDot() && !$path->isDir()) {
                    yield $dir.DIRECTORY_SEPARATOR.$path->getFilename();
                }
            }
        }

        return null;
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
