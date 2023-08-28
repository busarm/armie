<?php

namespace Armie\Service;

use Armie\Interfaces\ServiceHandlerInterface;
use Armie\Interfaces\SingletonInterface;
use Armie\Traits\Singleton;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
abstract class BaseService implements ServiceHandlerInterface, SingletonInterface
{
    use Singleton;

    /**
     * Get service location for name.
     *
     * @param string $name
     *
     * @return string|null
     */
    abstract protected function getLocation($name);
}
