<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Bags\Bag;
use Busarm\PhpMini\Bags\Query;
use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Dto\ServiceResponseDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;
use Busarm\PhpMini\Loader;
use Busarm\PhpMini\Request;
use Nyholm\Psr7\Uri;

use const Busarm\PhpMini\Constants\VAR_PATH_INFO;
use const Busarm\PhpMini\Constants\VAR_REQUEST_URI;

use function Busarm\PhpMini\Helpers\http_parse_query;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class LocalService extends BaseService
{
    public function __construct(
        private string $name,
        private ?string $location = null,
        private ?ServiceDiscoveryInterface $discovery = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function call(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto
    {
        $path = $this->location ?? $this->getLocation($this->name);
        if (empty($path)) {
            throw new SystemError(self::class . ": Location for client $this->name not found");
        }

        $path = is_dir($path) ? $path . '/index.php' : $path;
        if (!file_exists($path)) {
            throw new SystemError(self::class . ": Client $this->name App file not found: $path");
        }

        $uri = (new Uri(rtrim($request->baseUrl(), '/') . '/' . ltrim($dto->route, '/')));
        $query = http_parse_query($uri->getQuery());

        $dto->headers = $dto->headers ?? [];
        $dto->headers['x-trace-id'] = $request->correlationId();

        $server = $request->server();
        $server->set(VAR_REQUEST_URI, '/' . $dto->route);
        $server->set(VAR_PATH_INFO, '/' . $dto->route);

        $response = Loader::require($path, [
            'request' =>
            Request::fromUrl(
                strval($uri),
                match ($dto->type) {
                    ServiceType::CREATE => HttpMethod::POST,
                    ServiceType::UPDATE => HttpMethod::PUT,
                    ServiceType::DELETE => HttpMethod::DELETE,
                    default   => HttpMethod::GET,
                }
            )->initialize(
                new Query($dto->type == ServiceType::READ ? array_merge($dto->params, $query) : $query),
                new Bag($dto->type != ServiceType::READ ? $dto->params : []),
                $request->cookie(),
                null,
                new Bag($dto->files),
                $server,
                new Bag($dto->headers),
                null,
            )->toPsr(),
            'discovery' => $this->discovery,
        ]);

        if ($response instanceof ResponseInterface && $response->isSuccessful()) {
            return (new ServiceResponseDto)->setStatus(true)->setAsync(false)
                ->setCode($response->getStatusCode())
                ->setData($response->getParameters());
        } else if (is_array($response) || is_object($response)) {
            return (new ServiceResponseDto)->setStatus(true)->setAsync(false)
                ->setCode(200)
                ->setData((array)$response);
        }
        return (new ServiceResponseDto)->setStatus(false)->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto
    {
        throw new SystemError("Async local service request not currently supported.");
    }

    /**
     * Get service location for name
     * 
     * @param string $name
     * @return string|null
     */
    public function getLocation($name)
    {
        $client = $this->discovery?->getServiceClient($name);
        if ($client) {
            return $client->getLocation();
        }
        return null;
    }
}
