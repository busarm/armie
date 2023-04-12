<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

use const Busarm\PhpMini\Constants\VAR_CORRELATION_ID;

use function Busarm\PhpMini\Helpers\http_parse_query;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class RemoteService extends BaseService
{
    public function __construct(private ?ServiceDiscoveryInterface $discovery = null, private $timeout = 10)
    {
    }

    /**
     * @inheritDoc
     * @return ResponseInterface
     */
    public function call(ServiceRequestDto $dto, RequestInterface $request): ResponseInterface
    {
        $url = $dto->location ?? $this->getLocation($dto->name);

        if (empty($url)) {
            throw new SystemError(self::class . ": Location for client `$dto->name` not found");
        }

        if (!($url = filter_var($url, FILTER_VALIDATE_URL))) {
            throw new SystemError(self::class . ": Location for client `$dto->name` is not a valid remote url");
        }

        $uri = (new Uri(rtrim($url, '/') . '/' . ltrim($dto->route, '/')));
        $query = http_parse_query($uri->getQuery());

        $dto->headers = $dto->headers ?? [];
        $dto->headers[VAR_CORRELATION_ID] = $request->correlationId();

        $client = new Client([
            'timeout'  => $this->timeout,
        ]);
        return $client->request(
            match ($dto->type) {
                ServiceType::CREATE => HttpMethod::POST,
                ServiceType::UPDATE => HttpMethod::PUT,
                ServiceType::DELETE => HttpMethod::DELETE,
                default   => HttpMethod::GET,
            },
            $uri,
            $dto->type == ServiceType::READ ?
                [
                    RequestOptions::QUERY => array_merge($dto->params, $query),
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false,
                    RequestOptions::MULTIPART => $dto->files,
                ] :
                [
                    RequestOptions::QUERY => $query,
                    RequestOptions::BODY => $dto->params,
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false,
                    RequestOptions::MULTIPART => $dto->files,
                ]
        );
    }

    /**
     * @inheritDoc
     * @return PromiseInterface
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request): PromiseInterface
    {

        $url = $dto->location ?? $this->getLocation($dto->name);

        if (empty($url)) {
            throw new SystemError(self::class . ": Location for client `$dto->name` not found");
        }

        if (!($url = filter_var($url, FILTER_VALIDATE_URL))) {
            throw new SystemError(self::class . ": Location for client `$dto->name` is not a valid remote url");
        }

        $uri = (new Uri(rtrim($url, '/') . '/' . ltrim($dto->route, '/')));
        $query = http_parse_query($uri->getQuery());

        $dto->headers = $dto->headers ?? [];
        $dto->headers[VAR_CORRELATION_ID] = $request->correlationId();

        $client = new Client([
            'timeout'  => $this->timeout,
        ]);
        return $client->requestAsync(
            match ($dto->type) {
                ServiceType::CREATE => HttpMethod::POST,
                ServiceType::UPDATE => HttpMethod::PUT,
                ServiceType::DELETE => HttpMethod::DELETE,
                default   => HttpMethod::GET,
            },
            $uri,
            $dto->type == ServiceType::READ ?
                [
                    RequestOptions::QUERY => array_merge($dto->params, $query),
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false,
                    RequestOptions::MULTIPART => $dto->files,
                ] :
                [
                    RequestOptions::QUERY => $query,
                    RequestOptions::BODY => $dto->params,
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false,
                    RequestOptions::MULTIPART => $dto->files,
                ]
        );
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
