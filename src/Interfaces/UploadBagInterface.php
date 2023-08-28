<?php

namespace Armie\Interfaces;

use Stringable;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface UploadBagInterface extends Stringable
{
    /**
     * Set uploaded file.
     *
     * @param string                                  $name
     * @param \Psr\Http\Message\UploadedFileInterface $value
     * @param mixed                                   $options
     *
     * @return bool
     */
    public function set(string $name, \Psr\Http\Message\UploadedFileInterface $value, $options = null): bool;

    /**
     * Checks if an uploaded file exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get uploaded file.
     *
     * @param string                                       $name
     * @param \Psr\Http\Message\UploadedFileInterface|null $default
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    public function get(string $name, \Psr\Http\Message\UploadedFileInterface|null $default = null): \Psr\Http\Message\UploadedFileInterface;

    /**
     * Pull uploaded file: Get and delete.
     *
     * @param string                                       $name
     * @param \Psr\Http\Message\UploadedFileInterface|null $default
     *
     * @return \Psr\Http\Message\UploadedFileInterface
     */
    public function pull(string $name, \Psr\Http\Message\UploadedFileInterface|null $default = null): \Psr\Http\Message\UploadedFileInterface;

    /**
     * Get all uploaded files.
     *
     * @return \Psr\Http\Message\UploadedFileInterface[]
     */
    public function all(): array;

    /**
     * Set bulk uploaded files.
     *
     * @param \Psr\Http\Message\UploadedFileInterface[] $data
     *
     * @return void
     */
    public function replace(array $data);

    /**
     * Remove uploaded file.
     *
     * @param string $name
     *
     * @return void
     */
    public function remove(string $name);

    /**
     * Remove all uploaded files.
     *
     * @return void
     */
    public function clear();
}
