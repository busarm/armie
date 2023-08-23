<?php

namespace Armie\Service;

use Armie\Bags\Bag;
use Armie\Interfaces\ServiceClientInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Interfaces\StorageBagInterface;

/**
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class LocalServiceDiscovery implements ServiceDiscoveryInterface
{
    /**
     * @var StorageBagInterface<ServiceClientInterface>
     */
    protected readonly StorageBagInterface $services;

    /**
     * @param string|ServiceClientInterface[] $pathOrList Service discovery file path or list of services
     * - If file path, then file should be a json file with the list of services. Format = `{"name" : "url", ...}`
     */
    public function __construct(string|array $pathOrList)
    {
        if (is_string($pathOrList)) {
            $this->services = new Bag;
            if (file_exists($pathOrList)) {
                $list = json_decode(file_get_contents($pathOrList), true) ?? [];
                if (!empty($list)) {
                    foreach ($list as $name => $path) {
                        $this->services->set($name, new LocalClient($name, $path));
                    }
                }
            }
        } else {
            $this->services = new Bag($pathOrList);
        }
    }

    /**
     * Get service client
     *
     * @param string $name Service Name
     * @return ServiceClientInterface|null
     */
    public function getServiceClient(string $name): ServiceClientInterface|null
    {
        return $this->services->get($name);
    }

    /**
     * Get list of service client
     * @return ServiceClientInterface[]
     */
    public function getServiceClients(): array
    {
        return $this->services->all();
    }

    /**
     * Get `name=>location` map list of service clienta
     * 
     * @return array<string,string>
     */
    public function getServiceClientsMap(): array
    {
        return array_reduce($this->getServiceClients(), function ($carry, ServiceClientInterface $current) {
            $carry[$current->getName()] = $current->getLocation();
            return $carry;
        }, []);
    }
}
