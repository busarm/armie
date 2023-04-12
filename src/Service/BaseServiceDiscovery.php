<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Interfaces\ServiceClientInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class BaseServiceDiscovery implements ServiceDiscoveryInterface
{
    /**
     *
     * @param ServiceClientInterface[] $services
     */
    public function __construct(protected array $services = [])
    {
    }

    /**
     * Load service clients
     */
    abstract public function load();
}
