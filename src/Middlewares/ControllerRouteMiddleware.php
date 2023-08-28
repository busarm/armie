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
use ReflectionMethod;

use function Armie\Helpers\app;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class ControllerRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private $controller, private $function, private $params = [])
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
        if (class_exists($this->controller)) {
            // Load controller
            $object = app()->di->instantiate($this->controller, $request);
            if ($object) {
                // Load method
                if (
                    $object
                    && method_exists($object, $this->function)
                    && is_callable([$object, $this->function])
                ) {
                    $method = new ReflectionMethod($object, $this->function);
                    $result = app()->di->processMethodAttributes($method, $request);
                    if (!isset($result)) {
                        $result = $method->invoke(
                            $object,
                            ...array_merge(app()->di->resolveMethodDependencies(
                                $method,
                                $request,
                            ), $this->params)
                        );
                    }

                    if ($request instanceof RequestInterface) {
                        return $result !== false ?
                            (new ResponseHandler(data: $result, version: $request->version(), format: app()->config->http->responseFormat))->handle() :
                            throw new NotFoundException('Not found - '.($request->method()->value.' '.$request->path()));
                    }

                    return $result !== false ?
                        (new ResponseHandler(data: $result, format: app()->config->http->responseFormat))->handle() :
                        throw new NotFoundException('Resource not found');
                }

                throw new SystemError("Function not found or can't be executed: ".$this->controller.'::'.$this->function);
            }

            throw new SystemError('Failed to instantiate controller: '.$this->controller);
        }

        throw new SystemError('Class does not exist: '.$this->controller);
    }
}
