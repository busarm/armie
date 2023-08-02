<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Handlers\ResponseHandler;
use Closure;
use Busarm\PhpMini\DI;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\NotFoundException;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use ReflectionFunction;

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class CallableRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private Closure $callable, private $params = [])
    {
    }

    /**
     * Middleware handler
     *
     * @param RequestInterface|RouteInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(RequestInterface|RouteInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_callable($this->callable)) {

            $function = new ReflectionFunction($this->callable);
            $result = app()->di->processFunctionAttributes($function, $request);
            if (!isset($result)) {
                $result = $function->invoke(
                    ...array_merge(app()->di->resolveCallableDependencies(
                        $function,
                        $request,
                    ), $this->params)
                );
            }

            if ($request instanceof RequestInterface) {
                return $result !== false ?
                    (new ResponseHandler(data: $result, version: $request->version(), format: app()->config->http->responseFormat))->handle() :
                    throw new NotFoundException("Not found - " . ($request->method() . ' ' . $request->path()));
            }
            return $result !== false ?
                (new ResponseHandler(data: $result, format: app()->config->http->responseFormat))->handle() :
                throw new NotFoundException("Resource not found");
        }
        throw new SystemError("Callable route can't be executed");
    }
}
