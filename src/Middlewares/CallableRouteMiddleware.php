<?php

namespace Armie\Middlewares;

use Armie\Errors\SystemError;
use Armie\Exceptions\NotFoundException;
use Armie\Handlers\ResponseHandler;
use Armie\Interfaces\MiddlewareInterface;
use Armie\Interfaces\RequestHandlerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;
use Closure;
use ReflectionFunction;

use function Armie\Helpers\app;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class CallableRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private Closure $callable, private $params = [])
    {
    }

    /**
     * Middleware handler.
     *
     * @param RequestInterface|RouteInterface $request
     * @param RequestHandlerInterface         $handler
     *
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
                    throw new NotFoundException('Not found - ' . ($request->method()->value . ' ' . $request->path()));
            }

            return $result !== false ?
                (new ResponseHandler(data: $result, format: app()->config->http->responseFormat))->handle() :
                throw new NotFoundException('Resource not found');
        }

        throw new SystemError("Callable route can't be executed");
    }
}
