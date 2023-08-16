<?php

namespace Armie\Service;

use Armie\Bags\Bag;
use Armie\Dto\ServiceRegistryDto;
use Armie\Enums\HttpMethod;
use Armie\Interfaces\DistributedServiceDiscoveryInterface;
use Armie\Interfaces\ServiceClientInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Interfaces\StorageBagInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Error Reporting
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class DistributedServiceDiscovery implements DistributedServiceDiscoveryInterface, ServiceDiscoveryInterface
{
    /**
     * @var StorageBagInterface<ServiceRegistryDto>
     */
    protected ?StorageBagInterface $registry = null;

    protected bool $registered = false;

    /**
     *
     * @param RemoteClient $client Current client to be registered on the service registry
     * @param string $endpoint Service registry endpoint
     * - Endpoint must support
     * -- GET /{name}       - Get service client by name. Format = `{"name":"<name>", "url":"<url>"}`
     * -- POST /            - Register service client
     * -- DELETE /{name}    - Unregister service client. Accepts `url` as query param.
     * @param integer $ttl Service registry cache ttl (seconds). Re-load after ttl. Default: 300secs
     * @param integer $timeout Service registry request timeout (seconds). Default: 10secs
     */
    public function __construct(private RemoteClient $client, private string $endpoint, private $ttl = 300, private $timeout = 10)
    {
        $this->registry = new Bag();
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ?ServiceRegistryDto
    {
        if ($name == $this->client->getName()) return null;

        $service = $this->registry->get($name);
        if (!empty($service)) {
            if (($service->expiresAt > time())) {
                return $service;
            } else {
                $http = new Client([
                    'timeout'  => $this->timeout,
                ]);
                $response  = $http->request(
                    HttpMethod::GET->value,
                    $this->endpoint . "/$name",
                    [
                        RequestOptions::VERIFY => false
                    ]
                );
                if ($response->getStatusCode() == 200 && !empty($result = json_decode($response->getBody(), true))) {
                    if (isset($result['name']) && isset($result['url'])) {
                        $service = new ServiceRegistryDto($result['name'], $result['url'], time() + $this->ttl);
                        $this->registry->set($result['name'], new ServiceRegistryDto($result['name'], $result['url'], time() + $this->ttl));
                        return $service;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $http = new Client([
            'timeout'  => $this->timeout,
        ]);
        $response = $http->request(
            HttpMethod::POST->value,
            $this->endpoint,
            [
                RequestOptions::VERIFY => false,
                RequestOptions::BODY => [
                    'name' => $this->client->getName(),
                    'url' => $this->client->getLocation()
                ]
            ]
        );
        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            $this->registered = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function unregister(): void
    {
        $http = new Client([
            'timeout'  => $this->timeout,
        ]);
        $response = $http->request(
            HttpMethod::DELETE->value,
            $this->endpoint . "/" . $this->client->getName(),
            [
                RequestOptions::VERIFY => false,
                RequestOptions::QUERY => [
                    'url' => $this->client->getLocation()
                ]
            ]
        );
        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            $this->registered = false;
        }
    }

    /**
     * Get service client
     *
     * @param string $name Service Name
     * @return ?ServiceClientInterface
     */
    public function getServiceClient(string $name): ?ServiceClientInterface
    {
        if (!empty($service = $this->get($name))) {
            return new RemoteClient($service->name, $service->url);
        }
        return null;
    }

    /**
     * Get list of service client
     * @return ServiceClientInterface[]
     */
    public function getServiceClients(): array
    {
        return array_map(
            function (ServiceRegistryDto $service) {
                $service = $this->get($service->name);
                return new RemoteClient($service->name, $service->url);
            },
            $this->registry->all()
        );
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
