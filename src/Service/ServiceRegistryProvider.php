<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Dto\ServiceRegistryDto;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Exceptions\NotFoundException;
use Busarm\PhpMini\Interfaces\ProviderInterface;
use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Busarm\PhpMini\Attributes\Request\QueryParam;

/**
 * Use to generate a service registry server
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ServiceRegistryProvider implements ProviderInterface
{

    public function __construct(private StorageBagInterface $storage)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(App $app): void
    {
        // Get service endpoint
        $app->get("discovery/{name}")->call(function (string $name) {
            $list = $this->storage->get($name);
            if (!empty($list) && is_array($list)) {
                return $this->leastUsed($list);
            }
            throw new NotFoundException("Service not found for name: $name");
        });

        // Register service endpoint
        $app->post("discovery")->call(function (ServiceRegistryDto $dto) {
            if ($dto && $dto->name && filter_var($dto->url, FILTER_VALIDATE_URL)) {
                $list = $this->storage->get($dto->name, []);
                $list[] = $dto->toArray();
                $this->storage->set($dto->name, $list);
                return true;
            }
            throw new BadRequestException("Invaild service registry request");
        });

        // Unegister service endpoint
        $app->delete("discovery/{name}")->call(function (
            string $name,
            #[QueryParam('url', true)] string $url
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
    }

    /**
     * Get least used service
     *
     * @param array $list
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
