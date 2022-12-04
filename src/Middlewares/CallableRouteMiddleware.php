<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Handlers\ResponseHandler;
use Closure;
use Busarm\PhpMini\DI;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Exceptions\NotFoundException;
use Busarm\PhpMini\Handlers\DependencyResolver;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use TypeError;

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
            try {
                // Get dependency resolver
                $resolver = app()->getBinding(DependencyResolverInterface::class, DependencyResolver::class);
        
                $result = ($this->callable)(...array_merge(DI::resolveCallableDependencies(
                    $this->callable,
                    new $resolver($request),
                ), $this->params));

                if ($request instanceof RequestInterface) {
                    return $result !== false ?
                        (new ResponseHandler(data: $result, version: $request->version(), format: app()->config->httpResponseFormat))->handle() :
                        throw new NotFoundException("Not found - " . ($request->method() . ' ' . $request->uri()));
                }
                return $result !== false ?
                    (new ResponseHandler(data: $result, format: app()->config->httpResponseFormat))->handle() :
                    throw new NotFoundException("Resource not found");
            } catch (TypeError $th) {
                throw new BadRequestException("Invalid parameter(s): " . $th->getMessage());
            }
        }
        throw new SystemError("Callable route can't be executed");
    }
}
