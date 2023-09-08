<?php

namespace Armie\Bags;

use Armie\Async;
use Armie\Crypto;
use Armie\Helpers\Security;
use Armie\Interfaces\StorageBagInterface;
use Generator;

use function Armie\Helpers\serialize;
use function Armie\Helpers\stream_read;
use function Armie\Helpers\stream_write;
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

    /**
     * Last time store was loaded
     *
     * @var integer
     */
    protected int $loadTime = 0;

    /**
     * @param string  $basePath  Storage root folder
     * @param ?string $key       Secret key for encryption. Ignore to disable encryption
     * @param bool    $async     Save file asynchronously. Default: false
     */
    public function __construct(protected string $basePath, protected ?string $key = null, protected bool $async = false)
    {
    }

    /**
     * @inheritDoc
     */
    public function load(array $data): self
    {
        foreach ($data as $path => $item) {
            if ($this->set($path, $item)) {
                $this->loadTime = time();
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
        $path = $this->fullPath($path);
        $key = $this->key;

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
        $data = !empty($key) ? Crypto::encrypt($key, $data) : $data;

        // 4. Save
        $file = fopen($path, 'xb');

        if ($this->async) {
            return $file ? Async::streamEventLoop($file, false, function ($stream, $body) {
                stream_write($stream, $body);
                fflush($stream) && fclose($stream) && $stream = null;
            }, [$file, $data]) : false;
        } else {
            $done = $file ? stream_write($file, $data) : false;
            $file && fflush($file) && fclose($file) && $file = null;
            return $done;
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $path, $default = null, $sanitize = false): mixed
    {
        if ($this->has($path)) {
            $path = $this->fullPath($path);

            // 1. Read
            $file = fopen($path, 'rb');
            $data = $file ? stream_read($file) : null;
            $file && fclose($file);
            if (!is_null($data)) {
                // 2. Decrypt
                $data = !empty($this->key) ? Crypto::decrypt($this->key, $data) : $data;
                // 3. Unserialize
                if ($data && !is_null($parsed = unserialize($data))) {
                    $data = $parsed;
                }
                // 4. Sanitize
                $data = $sanitize ? Security::clean($data) : $data;
            }
            return $data ?? $default;
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
        foreach ($this->getFiles($this->basePath) as $path) {
            $fullPath = $this->fullPath($path);
            clearstatcache(true, $fullPath);
            if ((filemtime($fullPath) ?: filectime($fullPath)) > $this->loadTime) {
                $list[$path] = $this->get($path);
            }
        }

        return $list;
    }

    /**
     * @inheritDoc
     */
    public function itterate(bool $delete = false): Generator
    {
        foreach ($this->getFiles($this->basePath) as $path) {
            yield $path => $delete ? $this->pull($path) : $this->get($path);
        }

        return null;
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
        if ($this->async) {
            Async::withEventLoop(function () use ($path) {
                \file_exists($path) && \unlink($path);
            });
        } else {
            \file_exists($path) && \unlink($path);
        }
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        foreach ($this->getFiles($this->basePath) as $path) {
            $this->remove($path);
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
        $path = $this->isStorePath($path) ? $path : sha1($path) . self::STORAGE_EXT;

        return str_starts_with($path, $this->basePath) ? $path : $this->basePath . DIRECTORY_SEPARATOR . $path;
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
                if (!$path->isDot() && !$path->isDir() && $this->isStorePath($path->getFilename())) {
                    yield $path->getFilename();
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
