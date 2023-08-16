<?php

namespace Armie\Interfaces;

use Armie\Dto\ServiceRegistryDto;

/**
 * Error Reporting
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
     */
    public function register(): void;

    /**
     * Unregister service client
     */
    public function unregister(): void;
}
