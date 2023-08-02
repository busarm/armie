<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface ServiceDiscoveryInterface
{
    /**
     * Get service client
     * 
     * @param string $name  Service Name
     * @return ServiceClientInterface|null
     */
    public function getServiceClient(string $name): ServiceClientInterface|null;

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
