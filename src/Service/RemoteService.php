<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Traits\SingletonStateless;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class RemoteService extends BaseService
{
    use SingletonStateless;

    public function __construct(private RequestInterface $request, private $timeout = 10)
    {
        parent::__construct($request);
    }

    /**
     * Call service
     * 
     * @param ServiceRequestDto $dto
     * @return ResponseInterface
     */
    public function call(ServiceRequestDto $dto)
    {
        $url = $this->get($dto->name);

        if (empty($url)) {
            throw new SystemError(self::class . ": Location for client `$dto->name` not found");
        }

        if (!($url = filter_var($this->get($dto->name), FILTER_VALIDATE_URL))) {
            throw new SystemError(self::class . ": Location for client `$dto->name` is not a valid remote url");
        }

        if ($dto->name == $this->getCurrentServiceName()) {
            throw new SystemError(self::class . ": Circular request to current service `$dto->name` not allowed");
        }

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
            new Uri($url),
            $dto->type != ServiceType::READ ?
                [
                    RequestOptions::BODY => $dto->params,
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false
                ] :
                [
                    RequestOptions::QUERY => $dto->params,
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false
                ]
        );
    }

    /**
     * Call service asynchronously
     * 
     * @param ServiceRequestDto $dto
     * @return PromiseInterface
     */
    public function callAsync(ServiceRequestDto $dto)
    {
        $url = $this->get($dto->name);

        if (empty($url)) {
            throw new SystemError(self::class . ": Location for client `$dto->name` not found");
        }

        if (!($url = filter_var($this->get($dto->name), FILTER_VALIDATE_URL))) {
            throw new SystemError(self::class . ": Location for client `$dto->name` is not a valid remote url");
        }

        if ($dto->name == $this->getCurrentServiceName()) {
            throw new SystemError(self::class . ": Circular request to current service `$dto->name` not allowed");
        }

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
            new Uri($url),
            $dto->type != ServiceType::READ ?
                [
                    RequestOptions::BODY => $dto->params,
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false
                ] :
                [
                    RequestOptions::QUERY => $dto->params,
                    RequestOptions::HEADERS => $dto->headers,
                    RequestOptions::VERIFY => false
                ]
        );
    }
}
