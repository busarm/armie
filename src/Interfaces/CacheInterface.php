<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface CacheInterface
{
    /**
     * Initalize cache
     *
     * @param string $name Cache name
     * @param string $size Cache pool size. -1 for unlimited. Optional - Use if cache pooling is supported
     * @return void
     */
    public function initialize(string $name, $size = -1);
    /**
     * Set cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $expiry
     * @param array $tags
     * @return void
     */
    public function set(string $key, mixed $value, int $expiry, array $tags = []);
    /**
     * Get cache
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;
    /**
     * Get cache for tags
     *
     * @param array $tags
     * @return void
     */
    public function getTag(array $tags = []);
    /**
     * Remove cache for key
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key);
    /**
     * Remove cache for tags
     *
     * @param array $tags
     * @return void
     */
    public function removeTag(array $tags = []);
    /**
     * Delete all cache for cache id
     *
     * @return void
     */
    public function flush();
}
