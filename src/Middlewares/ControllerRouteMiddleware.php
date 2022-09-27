<?php

namespace Busarm\PhpMini\Middlewares;

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

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class ControllerRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private $controller, private $function, private $params = [])
    {
    }

    public function handle(RequestInterface|RouteInterface &$request, ResponseInterface &$response, callable $next = null): mixed
    {
        if (class_exists($this->controller)) {
            // Load controller
            $object = app()->make($this->controller);
            if ($object) {
                // Load method
                if (
                    $object
                    && method_exists($object, $this->function)
                    && is_callable(array($object, $this->function))
                ) {
                    try {
                        return call_user_func_array(
                            array($object, $this->function),
                            array_merge(DI::resolveMethodDependencies(
                                $this->controller,
                                $this->function,
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
                            ), $this->params)
                        );
                    } catch (TypeError $th) {
                        throw new BadRequestException("Invalid parameter(s): " . $th->getMessage());
                    }
                }
                throw new SystemError("Function not found or can't be executed: " . $this->controller . '::' . $this->function);
            }
            throw new SystemError("Failed to instantiate controller: " . $this->controller);
        }
        throw new SystemError("Class does not exist: " . $this->controller);
    }
}
