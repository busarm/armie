<?php

namespace Armie\Interfaces;

/**
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
interface ServiceDiscoveryInterface
{
    /**
     * Get service client
     * 
     * @param string $name  Service Name
     * @return ?ServiceClientInterface
     */
    public function getServiceClient(string $name): ?ServiceClientInterface;

    /**
     * Get list of service client
     * 
     * @return ServiceClientInterface[]
     */
    public function getServiceClients(): array;

    /**
     * Get `name=>location` map list of service clienta
     * 
     * @return array<string,string>
     */
    public function getServiceClientsMap(): array;
}
