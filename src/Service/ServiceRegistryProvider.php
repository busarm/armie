<?php

namespace Armie\Service;

use Armie\App;
use Armie\Dto\ServiceRegistryDto;
use Armie\Exceptions\BadRequestException;
use Armie\Exceptions\NotFoundException;
use Armie\Interfaces\ProviderInterface;
use Armie\Interfaces\StorageBagInterface;

/**
 * Use to generate a service registry server.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ServiceRegistryProvider implements ProviderInterface
{
    public const ROUTE = 'discovery';

    /**
     * @param StorageBagInterface<array> $storage
     */
    public function __construct(protected StorageBagInterface $storage)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(App $app): void
    {
        // Register service endpoint
        $app->post(self::ROUTE)->call(function (?ServiceRegistryDto $dto) {
            if ($dto && $dto->name && filter_var($dto->url, FILTER_VALIDATE_URL)) {
                $list = $this->storage->get($dto->name, []);
                $list[] = $dto->toArray();
                $this->storage->set($dto->name, $list);

                return true;
            }

            throw new BadRequestException('Invaild service registry request');
        });

        // Unregister service endpoint
        $app->delete(self::ROUTE . '/{name}/{url}')->call(function (
            string $name,
            string $url
        ) {
            $list = $this->storage->get($name);
            if (!empty($list) && is_array($list)) {
                foreach ($list as $key => $data) {
                    if ($data['url'] == $url) {
                        unset($list[$key]);
                    }
                }
                $this->storage->set($name, $list);
            }

            throw new NotFoundException("Service not found for name: $name");
        });

        // Get service endpoint
        $app->get(self::ROUTE . '/{name}')->call(function (string $name) {
            $list = $this->storage->get($name);
            if (!empty($list) && is_array($list)) {
                return $this->leastUsed($list);
            }

            throw new NotFoundException("Service not found for name: $name");
        });
    }

    /**
     * Get least used service.
     *
     * @param array $list
     *
     * @return ServiceRegistryDto
     */
    private function leastUsed(array $list): ServiceRegistryDto
    {
        if (count($list) == 1) {
            $data = $list[0];

            return new ServiceRegistryDto($data['name'], $data['url'], $data['expiresAt'], $data['requestCount']);
        } else {
            return array_reduce($list, function (ServiceRegistryDto|null $carry, $data) {
                if (!isset($carry) || intval($data['requestCount']) < $carry->requestCount) {
                    return new ServiceRegistryDto($data['name'], $data['url'], $data['expiresAt'], $data['requestCount']);
                } else {
                    return $carry;
                }
            }, null);
        }
    }
}
