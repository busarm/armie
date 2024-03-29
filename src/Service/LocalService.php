<?php

namespace Armie\Service;

use Armie\Bags\Bag;
use Armie\Bags\Query;
use Armie\Dto\ServiceRequestDto;
use Armie\Dto\ServiceResponseDto;
use Armie\Enums\HttpMethod;
use Armie\Enums\ServiceType;
use Armie\Errors\SystemError;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Loader;
use Armie\Request;
use Nyholm\Psr7\Uri;

use function Armie\Helpers\async;
use function Armie\Helpers\http_parse_query;

use const Armie\Constants\VAR_PATH_INFO;
use const Armie\Constants\VAR_REQUEST_URI;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class LocalService extends BaseService
{
    /**
     * @param string                         $name      Name of service
     * @param string|null                    $location  File Path location of service
     * @param ServiceDiscoveryInterface|null $discovery Service discovery
     */
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
        $dto->headers['x-trace-id'] = $dto->headers['x-correlation-id'] = $request->correlationId();

        $server = $request->server();
        $server->set(VAR_REQUEST_URI, '/' . $dto->route);
        $server->set(VAR_PATH_INFO, '/' . $dto->route);

        $response = Loader::require($path, [
            'request' => Request::fromUrl(
                strval($uri),
                match ($dto->type) {
                    ServiceType::CREATE => HttpMethod::POST,
                    ServiceType::UPDATE => HttpMethod::PUT,
                    ServiceType::DELETE => HttpMethod::DELETE,
                    default             => HttpMethod::GET,
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
            return (new ServiceResponseDto())->setStatus(true)->setAsync(false)
                ->setCode($response->getStatusCode())
                ->setData($response->getParameters());
        } elseif (is_array($response) || is_object($response)) {
            return (new ServiceResponseDto())->setStatus(true)->setAsync(false)
                ->setCode(200)
                ->setData((array) $response);
        }

        return (new ServiceResponseDto())->setStatus(false)->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto
    {
        $path = $this->location ?? $this->getLocation($this->name);
        if (empty($path)) {
            throw new SystemError(self::class . ": Location for client $this->name not found");
        }

        $path = is_dir($path) ? $path . '/index.php' : $path;
        if (!file_exists($path)) {
            throw new SystemError(self::class . ": Client $this->name App file not found: $path");
        }

        $baseUrl = $request->baseUrl();

        $dto->headers = $dto->headers ?? [];
        $dto->headers['x-trace-id'] = $dto->headers['x-correlation-id'] = $request->correlationId();

        $server = $request->server();
        $server->set(VAR_REQUEST_URI, '/' . $dto->route);
        $server->set(VAR_PATH_INFO, '/' . $dto->route);
        $serverList = $server->all();

        $cookieList = $request->cookie()->all();

        $discovery = $this->discovery;

        // Call async
        async(static function () use ($path, $baseUrl, $dto, $discovery, $cookieList, $serverList) {
            $uri = (new Uri(rtrim($baseUrl, '/') . '/' . ltrim($dto->route, '/')));
            $query = http_parse_query($uri->getQuery());

            // Load local service with path
            Loader::require($path, [
                'request' => Request::fromUrl(
                    strval($uri),
                    match ($dto->type) {
                        ServiceType::CREATE => HttpMethod::POST,
                        ServiceType::UPDATE => HttpMethod::PUT,
                        ServiceType::DELETE => HttpMethod::DELETE,
                        default             => HttpMethod::GET,
                    }
                )->initialize(
                    new Query($dto->type == ServiceType::READ ? array_merge($dto->params, $query) : $query),
                    new Bag($dto->type != ServiceType::READ ? $dto->params : []),
                    new Bag($cookieList),
                    null,
                    new Bag($dto->files),
                    new Bag($serverList),
                    new Bag($dto->headers),
                    null,
                )->toPsr(),
                'discovery' => $discovery,
            ]);
        });

        return (new ServiceResponseDto())
            ->setStatus(true)
            ->setAsync(true)
            ->setData([]);
    }

    /**
     * @inheritDoc
     */
    protected function getLocation($name)
    {
        $client = $this->discovery?->getServiceClient($name);
        if ($client && $client instanceof LocalClient) {
            return $client->getLocation();
        }

        return null;
    }
}
