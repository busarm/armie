<?php

namespace Armie\Service;

use Armie\Bags\Bag;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Interfaces\StorageBagInterface;

/**
 * Load remote service registry from external source
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class RemoteServiceDiscovery implements ServiceDiscoveryInterface
{
    /**
     * @var StorageBagInterface<RemoteClient>
     */
    protected readonly StorageBagInterface $services;

    /**
     * Last registry request date
     * 
     * @var integer
     */
    private int $requestedAt = 0;

    /**
     * @param string $endpoint Service registry url endpoint
     * - If url or file path, the file or response should be a JSON with the list of services. Format = `{"name" : "url", ...}`
     * @param integer $ttl Service registry cache ttl (seconds). Reload list after ttl
     */
    public function __construct(protected string $endpoint, protected int $ttl = 300)
    {
        $this->services = new Bag;
        $this->load();
    }

    /**
     * Get service client
     *
     * @param string $name Service Name
     * @return RemoteClient
     */
    public function getServiceClient(string $name): RemoteClient|null
    {
        if ($this->hasExpired()) {
            $this->load();
        }
        return $this->get(($name));
    }

    /**
     * Get list of service client
     * @return RemoteClient[]
     */
    public function getServiceClients(): array
    {
        if ($this->hasExpired()) {
            $this->load();
        }
        return $this->services->all();
    }

    /**
     * Get `name=>location` map list of service clienta
     * 
     * @return array<string,string>
     */
    public function getServiceClientsMap(): array
    {
        return array_reduce($this->getServiceClients(), function ($carry, RemoteClient $current) {
            $carry[$current->getName()] = $current->getLocation();
            return $carry;
        }, []);
    }

    /**
     * Check if registry cache has expired
     */
    private function hasExpired(): bool
    {
        return $this->requestedAt && ($this->requestedAt + $this->ttl <= time());
    }

    /**
     * Get service client
     *
     * @param string $name Service Name
     * @return ?RemoteClient
     */
    private function get(string $name): ?RemoteClient
    {
        return $this->services->get($name);
    }

    /**
     * Load service clients
     */
    private function load(): void
    {
        if ((filter_var($this->endpoint, FILTER_VALIDATE_URL) || file_exists($this->endpoint))) {
            $list = json_decode(file_get_contents($this->endpoint), true) ?? [];
            if (!empty($list)) {
                foreach ($list as $name => $path) {
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        $this->services->set($name, new RemoteClient($name, $path));
                    }
                }
                $this->requestedAt = time();
            }
        }
    }
}
