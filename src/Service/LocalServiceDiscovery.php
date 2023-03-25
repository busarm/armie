<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Interfaces\ServiceClientInterface;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class LocalServiceDiscovery extends BaseServiceDiscovery
{
    /**
     *
     *
     * @param string|ServiceClientInterface[] $pathOrList Service discovery file path or list of services
     * - If fiel path, then file should be a json file with the list of services. Format = `{"name" : "url"}`
     */
    public function __construct(private string|array $pathOrList)
    {
        if (is_array($pathOrList)) {
            parent::__construct($pathOrList);
        } else {
            parent::__construct();
            $this->load();
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
        foreach ($this->services as $service) {
            if (strtolower($name) === strtolower($service->getName())) {
                return $service;
            }
        }
        return null;
    }

    /**
     * Get list of service client
     * @return ServiceClientInterface[]
     */
    public function getServiceClients(): array
    {
        return $this->services;
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

    /**
     * Load service clients
     */
    public function load()
    {
        if (is_string($this->pathOrList) && file_exists($this->pathOrList)) {
            $this->services = json_decode(file_get_contents($this->pathOrList), true) ?? $this->services;
        }
    }
}
