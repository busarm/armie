<?php

namespace Busarm\PhpMini\Middlewares;

use Closure;
use Busarm\PhpMini\DI;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Response;
use Busarm\PhpMini\Route;
use TypeError;

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

    public function handle(RequestInterface|RouteInterface &$request, ResponseInterface &$response, callable $next = null): mixed
    {
        if (is_callable($this->callable)) {
            try {
                return ($this->callable)(...array_merge(DI::resolveCallableDependencies(
                    $this->callable,
                    // Resolver
                    function ($class) use (&$request, &$response) {
                        if (($class == Request::class || $class == RequestInterface::class) && $request instanceof RequestInterface) {
                            return $request;
                        } else if (($class == Route::class || $class == RouteInterface::class) && $request instanceof RouteInterface) {
                            return $request;
                        } else if ($class == Response::class || $class == ResponseInterface::class) {
                            return $response;
                        }
                        return null;
                    },
                    // Callback
                    function (&$param) use (&$request) {
                        if ($param instanceof BaseDto) {
                            if ($request instanceof RequestInterface) {
                                $param->load($request->getRequestList(), true);
                            } else if ($request instanceof RouteInterface) {
                                $param->load($request->getParams(), true);
                            }
                        } else if ($param instanceof CollectionBaseDto) {
                            if ($request instanceof RequestInterface) {
                                $param->load($request->getRequestList(), true);
                            } else if ($request instanceof RouteInterface) {
                                $param->load($request->getParams());
                            }
                        }
                    }
                ), $this->params));
            } catch (TypeError $th) {
                throw new BadRequestException("Invalid parameter(s): " . $th->getMessage());
            }
        }
        throw new SystemError("Callable route can't be executed");
    }
}
