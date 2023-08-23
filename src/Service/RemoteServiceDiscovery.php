<?php

namespace Armie\Service;

use Armie\Bags\Bag;
use Armie\Enums\HttpMethod;
use Armie\Interfaces\ServiceClientInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Interfaces\StorageBagInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class RemoteServiceDiscovery implements ServiceDiscoveryInterface
{
    /**
     * @var StorageBagInterface<ServiceClientInterface>
     */
    protected readonly StorageBagInterface $services;

    /**
     * Last registry request date
     * 
     * @var integer
     */
    private int $requestedAt = 0;

    /**
     *
     * @param string $endpoint Service registry endpoint
     * - Endpoint must be an HTTP GET request and should provide a response of list of services. Format = `{"name" : "url", ...}`
     * @param integer $ttl Service registry cache ttl (seconds). Re-load list after ttl
     * @param integer $timeout Service registry request timeout (seconds)
     */
    public function __construct(protected string $endpoint, protected $ttl = 300, protected $timeout = 10)
    {
        $this->services = new Bag();

        $this->load();
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

    /**
     * Get service client
     *
     * @param string $name Service Name
     * @return ?ServiceClientInterface
     */
    private function get(string $name): ?ServiceClientInterface
    {
        return $this->services->get($name);
    }

    /**
     * Load service clients
     */
    private function load()
    {
        $client = new Client([
            'timeout'  => $this->timeout,
        ]);
        $response = $client->request(
            HttpMethod::GET->value,
            $this->endpoint,
            [
                RequestOptions::VERIFY => false
            ]
        );

        if ($response->getBody()) {
            $result = json_decode($response->getBody(), true) ?? [];
            if (!empty($result)) {
                foreach ($result as $name => $url) {
                    if ($url = filter_var($url, FILTER_VALIDATE_URL)) {
                        $this->services->set($name, new RemoteClient($name, $url));
                    }
                }
                $this->requestedAt = time();
            }
        }
    }
}
