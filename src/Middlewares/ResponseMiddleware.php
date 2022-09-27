<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Interfaces\Arrayable;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseHandlerInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class ResponseMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface|RouteInterface &$request, ResponseInterface &$response, callable $next = null): mixed
    {
        $result = $next ? $next() : null;
        if ($result !== false) {
            if ($result !== null) {
                if ($result instanceof ResponseInterface) {
                    return $result->send(app()->config->httpResponseFormat, app()->config->httpSendAndContinue);
                } else if ($result instanceof ResponseHandlerInterface) {
                    return $result->handle($response, app()->config->httpSendAndContinue);
                } else if ($result instanceof Arrayable) {
                    return $response
                        ->setStatusCode(200)
                        ->setParameters($result->toArray())
                        ->send(app()->config->httpResponseFormat, app()->config->httpSendAndContinue);
                } else if (is_array($result) || is_object($result)) {
                    return $response
                        ->setStatusCode(200)
                        ->setParameters((array) $result)
                        ->send(app()->config->httpResponseFormat, app()->config->httpSendAndContinue);
                }
            }
            return $response->html((string) $result, 200, app()->config->httpSendAndContinue);
        }
        return false;
    }
}
