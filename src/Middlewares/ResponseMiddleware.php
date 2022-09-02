<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\View;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class ResponseMiddleware implements MiddlewareInterface
{
    public function handle(App $app, callable $next = null): mixed
    {
        $response = $next ? $next() : null;
        if ($response !== false) {
            if ($response instanceof ResponseInterface) {
                return $response->send('json', $app->config->httpSendAndContinue);
            } else if ($response instanceof View) {
                return $response->send($app->config->httpSendAndContinue);
            } else if ($response instanceof CollectionBaseDto) {
                return $app->sendHttpResponse(200, $response->toArray());
            } else if ($response instanceof BaseDto) {
                return $app->sendHttpResponse(200, $response->toArray());
            } else if (is_array($response) || is_object($response)) {
                return $app->sendHttpResponse(200, $response);
            } else {
                return $app->response->html((string) $response, 200, $app->config->httpSendAndContinue);
            }
        }
        return false;
    }
}
