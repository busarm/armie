<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
interface DistributedServiceDiscoveryInterface
{
    /**
     * Get service.
     *
     * @param string $name Service Name
     *
     * @return ?ServiceClientInterface
     */
    public function get(string $name): ?ServiceClientInterface;

    /**
     * Register service client.
     *
     * @param ServiceClientInterface $client
     *
     * @return void
     */
    public function register(ServiceClientInterface $client): void;

    /**
     * Unregister service client.
     *
     * @param ServiceClientInterface $client
     *
     * @return void
     */
    public function unregister(ServiceClientInterface $client): void;
}
