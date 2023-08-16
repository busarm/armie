<?php

namespace Armie\Interfaces;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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