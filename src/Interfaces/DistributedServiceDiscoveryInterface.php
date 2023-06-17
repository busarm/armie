<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Dto\ServiceRegistryDto;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface DistributedServiceDiscoveryInterface
{

    /**
     * Get service
     *
     * @param string $name Service Name
     * @return ?ServiceRegistryDto
     */
    public function get(string $name): ?ServiceRegistryDto;

    /**
     * Register service client
     *
     * @param ?RemoteClient $client
     */
    public function register(): void;

    /**
     * Unregister service client
     *
     * @param ?RemoteClient $client
     */
    public function unregister(): void;
}
