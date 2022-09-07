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
    public function view(string $path, $params = [], $return = false): ?string;
    public function config(string $path);
}