<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface LoaderInterface
{
    /**
     * Load view file
     *
     * @param string $path
     * @param array $params
     * @param boolean $return
     * @return string|null
     */
    public function view(string $path, $params = [], $return = false): ?string;

    /**
     * Load config file
     *
     * @param string $path
     * @return mixed
     */
    public function config(string $path): mixed;
}