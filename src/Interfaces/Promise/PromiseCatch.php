<?php

namespace Busarm\PhpMini\Interfaces\Promise;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface PromiseCatch extends PromiseFinal
{
    /**
     * @param callable(\Throwable $th): void $fn 
     */
    public function catch(callable $fn): PromiseFinal;
}
