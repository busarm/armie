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
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class DistributedServiceDiscovery implements DistributedServiceDiscoveryInterface, ServiceDiscoveryInterface
{
    protected bool $registered = false;

    /**
     * @param string                                  $endpoint Service registry endpoint
     *                                                          #### Endpoint must expose:
     *                                                          - `GET /{name}`           - Get service client by name. Format: `{"name":"<name>", "url":"<url>"}`
     *                                                          - `POST /`                - Register current service client. Accepts body with format: `{"name":"<name>", "url":"<url>"}`
     *                                                          - `DELETE /{name}/{url}`  - Unregister current service client
     * @param int                                     $timeout  Service registry request timeout (seconds). Default: 10secs
     * @param int                                     $ttl      Service registry cache ttl (seconds). Re-load after ttl. Default: 300secs
     * @param StorageBagInterface<ServiceRegistryDto> $store    Service registry cache store. Default: Bag (memory store)
     */
    public function __construct(protected string $endpoint, protected $timeout = 10, protected $ttl = 300, protected ?StorageBagInterface $store = null)
    {
        $this->store = $store ?? new Bag();
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ?ServiceClientInterface
    {
        $service = $this->store->get($name);
        if (!empty($service)) {
            if ($service->expiresAt > time()) {
                return new RemoteClient($service->name, $service->url);
            } else {
                $http = new Client([
                    'timeout'  => $this->timeout,
                ]);
                $response = $http->request(
                    HttpMethod::GET->value,
                    $this->endpoint."/$name",
                    [
                        RequestOptions::VERIFY => false,
                    ]
                );
                if ($response->getStatusCode() == 200 && !empty($result = json_decode($response->getBody(), true))) {
                    if (isset($result['name']) && isset($result['url'])) {
                        $service = new ServiceRegistryDto($result['name'], $result['url'], time() + $this->ttl);
                        $this->store->set($service->name, new ServiceRegistryDto($service->name, $service->url, time() + $this->ttl));

                        return new RemoteClient($service->name, $service->url);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function register(ServiceClientInterface $client): void
    {
        $http = new Client([
            'timeout'  => $this->timeout,
        ]);
        $response = $http->request(
            HttpMethod::POST->value,
            $this->endpoint,
            [
                RequestOptions::VERIFY => false,
                RequestOptions::BODY   => [
                    'name' => $client->getName(),
                    'url'  => $client->getLocation(),
                ],
            ]
        );
        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            $this->registered = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function unregister(ServiceClientInterface $client): void
    {
        $http = new Client([
            'timeout'  => $this->timeout,
        ]);
        $response = $http->request(
            HttpMethod::DELETE->value,
            $this->endpoint.'/'.$client->getName().'/'.urlencode($client->getLocation()),
            [
                RequestOptions::VERIFY => false,
            ]
        );
        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            $this->registered = false;
        }
    }

    /**
     * Get service client.
     *
     * @param string $name Service Name
     *
     * @return ?ServiceClientInterface
     */
    public function getServiceClient(string $name): ?ServiceClientInterface
    {
        return $this->get($name);
    }

    /**
     * Get list of service client.
     *
     * @return ServiceClientInterface[]
     */
    public function getServiceClients(): array
    {
        return array_map(
            function (ServiceRegistryDto $service) {
                return $this->get($service->name);
            },
            $this->store->all()
        );
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
}
