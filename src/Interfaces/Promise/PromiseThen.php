<?php

namespace Busarm\PhpMini\Interfaces\Promise;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 * @template T
 */
interface PromiseThen
{
    /**
     * @param callable(T $data): T $fn 
     */
    public function then(callable $fn): PromiseCatch;
}
