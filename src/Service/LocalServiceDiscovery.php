<?php

namespace Armie\Service;

use Armie\Bags\Bag;
use Armie\Interfaces\ServiceClientInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Interfaces\StorageBagInterface;

/**
 * Load local/remote service registry from local source such as: file or array list.
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
     *                                                    - If file path, the file should be a JSON with the list of services. Format = `{"name" : "path", ...}`
     */
    public function __construct(protected string|array $pathOrList)
    {
        if (is_array($pathOrList)) {
            $this->services = new Bag($pathOrList);
        } else {
            $this->services = new Bag();
            $this->load();
        }
    }

    /**
     * Get service client.
     *
     * @param string $name Service Name
     *
     * @return ServiceClientInterface|null
     */
    public function getServiceClient(string $name): ServiceClientInterface|null
    {
        return $this->services->get($name);
    }

    /**
     * Get list of service client.
     *
     * @return ServiceClientInterface[]
     */
    public function getServiceClients(): array
    {
        return $this->services->all();
    }

    /**
     * Get `name=>location` map list of service clienta.
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

    /**
     * Load service clients.
     */
    private function load(): void
    {
        if (is_string($this->pathOrList) && file_exists($this->pathOrList)) {
            $list = json_decode(file_get_contents($this->pathOrList), true) ?? [];
            if (!empty($list)) {
                foreach ($list as $name => $path) {
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        $this->services->set($name, new RemoteClient($name, $path));
                    } elseif (is_dir($path) || file_exists($path)) {
                        $this->services->set($name, new LocalClient($name, $path));
                    }
                }
            }
        }
    }
}
