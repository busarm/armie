<?php

namespace Armie\Interfaces;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ResponseHandlerInterface
{
    public function handle(): ResponseInterface;
}
