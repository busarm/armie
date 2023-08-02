<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Interfaces\ServiceHandlerInterface;
use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Traits\Singleton;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class BaseService implements ServiceHandlerInterface, SingletonInterface
{
    use Singleton;

    /**
     * Get service location for name
     * 
     * @param string $name
     * @return string|null
     */
    abstract protected function getLocation($name);
}
