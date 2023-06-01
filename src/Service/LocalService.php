<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Bags\Attribute;
use Busarm\PhpMini\Bags\Query;
use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;
use Busarm\PhpMini\Loader;
use Busarm\PhpMini\Request;
use Nyholm\Psr7\Uri;

use const Busarm\PhpMini\Constants\VAR_CORRELATION_ID;

use function Busarm\PhpMini\Helpers\http_parse_query;
use function Busarm\PhpMini\Helpers\run;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class LocalService extends BaseService
{
    public function __construct(private ?ServiceDiscoveryInterface $discovery = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function call(ServiceRequestDto $dto, RequestInterface $request)
    {
        $path = $dto->location ?? $this->getLocation($dto->name);
        if (empty($path)) {
            throw new SystemError(self::class . ": Location for client $dto->name not found");
        }

        $path = is_dir($path) ? $path . '/index.php' : $path;
        if (!file_exists($path)) {
            throw new SystemError(self::class . ": Client $dto->name App file not found: $path");
        }

        $uri = (new Uri(rtrim($request->baseUrl(), '/') . '/' . ltrim($dto->route, '/')));
        $query = http_parse_query($uri->getQuery());

        $dto->headers = $dto->headers ?? [];
        $dto->headers[VAR_CORRELATION_ID] = $request->correlationId();

        $server = $request->server();
        $server->set('REQUEST_URI', '/' . $dto->route);
        $server->set('PATH_INFO', '/' . $dto->route);

        return Loader::require($path, [
            'request' =>
            Request::fromUrl(
                strval($uri),
                match ($dto->type) {
                    ServiceType::CREATE => HttpMethod::POST,
                    ServiceType::UPDATE => HttpMethod::PUT,
                    ServiceType::DELETE => HttpMethod::DELETE,
                    default   => HttpMethod::GET,
                }
            )
                ->initialize(
                    new Query($dto->type == ServiceType::READ ? array_merge($dto->params, $query) : $query),
                    new Attribute($dto->type != ServiceType::READ ? $dto->params : []),
                    $request->cookie(),
                    $request->session(),
                    new Attribute($dto->files),
                    $server,
                    new Attribute($dto->headers),
                    null,
                )->toPsr(),
            'discovery' => $this->discovery,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request)
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
