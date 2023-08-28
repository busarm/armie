<?php

namespace Armie\Interfaces;

use Armie\Interfaces\ResponseInterface;
use Throwable;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
interface ErrorHandlerInterface
{
    /**
     * @param Throwable $throwable
     * @return ResponseInterface
     */
    public function handle(Throwable $throwable): ResponseInterface;
}
