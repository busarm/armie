<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\ServiceClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class RemoteServiceDiscovery extends BaseServiceDiscovery
{
    private int $requestedAt = 0;

    /**
     *
     * @param string $endpoint Service discovery endpoint
     * - Endpoint must be an HTTP GET request and should provide a response of list of services. Format = `{"name" : "url"}`
     * @param integer $ttl Service discovery cache ttl (seconds). Re-load list after ttl
     * @param integer $timeout Service discovery request timeout (seconds)
     */
    public function __construct(private string $endpoint, private $ttl = 300, private $timeout = 10)
    {
        $this->load();
    }

    /**
     * Get service client
     *
     * @param string $name Service Name
     * @return ServiceClientInterface
     */
    private function get(string $name): ServiceClientInterface|null
    {
        foreach ($this->services as $service) {
            if (strtolower($name) === strtolower($service->getName())) {
                return $service;
            }
        }
        return null;
    }

    /**
     * Get service client
     *
     * @param string $name Service Name
     * @return ServiceClientInterface
     */
    public function getServiceClient(string $name): ServiceClientInterface|null
    {
        if (($this->requestedAt + $this->ttl <= time()) ||
            !($client = $this->get(($name)))
        ) {
            $this->load();
            $client = $this->get(($name));
        }
        return $client ?? null;
    }

    /**
     * Get list of service client
     * @return ServiceClientInterface[]
     */
    public function getServiceClients(): array
    {
        if (($this->requestedAt + $this->ttl <= time())) {
            $this->load();
        }
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
        $client = new Client([
            'timeout'  => $this->timeout,
        ]);
        $response = $client->request(
            HttpMethod::GET,
            $this->endpoint,
            [
                RequestOptions::VERIFY => false
            ]
        );
        if ($response && $response->getBody()) {
            $result = json_decode($response->getBody(), true) ?? [];
            if (!empty($result)) {
                $this->services = [];
                foreach ($result as $name => $url) {
                    if ($url = filter_var($url, FILTER_VALIDATE_URL)) {
                        $this->services[] = new RemoteClient($name, $url);
                    }
                }
                $this->requestedAt = time();
            }
        }
    }
}
