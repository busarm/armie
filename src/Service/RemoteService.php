<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Dto\ServiceResponseDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Nyholm\Psr7\Uri;

use function Busarm\PhpMini\Helpers\async;
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
    public function __construct(
        private string $name,
        private ?string $location = null,
        private ?ServiceDiscoveryInterface $discovery = null,
        private $timeout = 10
    ) {
    }

    /**
     * @inheritDoc
     */
    public function call(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto
    {
        $url = $this->location ?? $this->getLocation($this->name);

        if (empty($url)) {
            throw new SystemError(self::class . ": Location for client `$this->name` not found");
        }

        if (!($url = filter_var($url, FILTER_VALIDATE_URL))) {
            throw new SystemError(self::class . ": Location for client `$this->name` is not a valid remote url");
        }

        $uri = (new Uri(rtrim($url, '/') . '/' . ltrim($dto->route, '/')));
        $query = http_parse_query($uri->getQuery());

        $dto->headers = $dto->headers ?? [];
        $dto->headers['x-trace-id'] = $request->correlationId();

        // Call remote service
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
                    RequestOptions::MULTIPART => !empty($dto->files) ? $dto->files : null,
                ] :
                [
                    RequestOptions::QUERY => $query,
                    RequestOptions::BODY => $dto->params,
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false,
                    RequestOptions::MULTIPART => !empty($dto->files) ? $dto->files : null,
                ]
        )->then(function (Response $response) {
            return (new ServiceResponseDto)
                ->setStatus($response->getStatusCode() == 200 || $response->getStatusCode() == 201)
                ->setAsync(false)
                ->setCode($response->getStatusCode())
                ->setData(json_decode($response->getBody(), true) ?? []);
        })->wait(true);
    }

    /**
     * @inheritDoc
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto
    {
        $url = $this->location ?? $this->getLocation($this->name);

        if (empty($url)) {
            throw new SystemError(self::class . ": Location for client `$this->name` not found");
        }

        if (!($url = filter_var($url, FILTER_VALIDATE_URL))) {
            throw new SystemError(self::class . ": Location for client `$this->name` is not a valid remote url");
        }

        $dto->headers = $dto->headers ?? [];
        $dto->headers['x-trace-id'] = $request->correlationId();

        $timeout = $this->timeout;

        // Call async
        async(function () use ($url, $dto, $timeout) {

            $uri = (new Uri(rtrim($url, '/') . '/' . ltrim($dto->route, '/')));
            $query = http_parse_query($uri->getQuery());

            // Call remote service
            $client = new Client([
                'timeout'  => $timeout,
            ]);
            $client->requestAsync(
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
                        RequestOptions::MULTIPART => !empty($dto->files) ? $dto->files : null,
                    ] :
                    [
                        RequestOptions::QUERY => $query,
                        RequestOptions::BODY => $dto->params,
                        RequestOptions::HEADERS => $dto->headers,
                        RequestOptions::VERIFY => false,
                        RequestOptions::MULTIPART => !empty($dto->files) ? $dto->files : null,
                    ]
            );
        });

        return (new ServiceResponseDto)
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
        if ($client) {
            return $client->getLocation();
        }
        return null;
    }
}
