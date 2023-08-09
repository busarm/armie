<?php

namespace Busarm\PhpMini\Interfaces\Promise;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface PromiseFinal
{
    /**
     * @param callable $fn 
     */
    public function finally(callable $fn): void;
}
