<?php

namespace Armie\Interfaces\Promise;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 * @template T
 */
interface PromiseThen extends PromiseCatch
{
    /**
     * @param callable(T $data): T $fn 
     */
    public function then(callable $fn): PromiseThen;
}
