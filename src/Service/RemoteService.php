<?php

namespace Armie\Service;

use Armie\Dto\ServiceRequestDto;
use Armie\Dto\ServiceResponseDto;
use Armie\Enums\HttpMethod;
use Armie\Enums\ServiceType;
use Armie\Errors\SystemError;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Nyholm\Psr7\Uri;

use function Armie\Helpers\async;
use function Armie\Helpers\http_parse_query;

/**
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class RemoteService extends BaseService
{
    public function __construct(
        protected string $name,
        protected ?string $location = null,
        protected ?ServiceDiscoveryInterface $discovery = null,
        protected $timeout = 10
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
        $method = match ($dto->type) {
            ServiceType::CREATE => HttpMethod::POST,
            ServiceType::UPDATE => HttpMethod::PUT,
            ServiceType::DELETE => HttpMethod::DELETE,
            default   => HttpMethod::GET,
        };
        $response = $client->request(
            $method->value,
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

        return (new ServiceResponseDto)
            ->setStatus($response->getStatusCode() == 200 || $response->getStatusCode() == 201)
            ->setAsync(false)
            ->setCode($response->getStatusCode())
            ->setData(json_decode($response->getBody(), true) ?? []);
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
        async(static function () use ($url, $dto, $timeout) {

            $uri = (new Uri(rtrim($url, '/') . '/' . ltrim($dto->route, '/')));
            $query = http_parse_query($uri->getQuery());

            // Call remote service
            $client = new Client([
                'timeout'  => $timeout,
            ]);
            $method = match ($dto->type) {
                ServiceType::CREATE => HttpMethod::POST,
                ServiceType::UPDATE => HttpMethod::PUT,
                ServiceType::DELETE => HttpMethod::DELETE,
                default   => HttpMethod::GET,
            };
            $client->request(
                $method->value,
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
            ->setAsync(true);
    }

    /**
     * @inheritDoc
     */
    protected function getLocation($name)
    {
        $client = $this->discovery?->getServiceClient($name);
        if ($client && $client instanceof RemoteClient) {
            return $client->getLocation();
        }
        return null;
    }
}
