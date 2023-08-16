<?php

namespace Armie\Interfaces\Promise;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface PromiseFinal
{
    /**
     * @param callable $fn 
     */
    public function finally(callable $fn): void;
}
