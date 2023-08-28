<?php

namespace Armie\Interfaces\Promise;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface PromiseCatch extends PromiseFinal
{
    /**
     * @param callable(\Throwable $th): void $fn
     */
    public function catch(callable $fn): PromiseFinal;
}
