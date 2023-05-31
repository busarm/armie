<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\DI;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\NotFoundException;
use Busarm\PhpMini\Handlers\ResponseHandler;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use ReflectionMethod;

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

    /**
     * Middleware handler
     *
     * @param RequestInterface|RouteInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(RequestInterface|RouteInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (class_exists($this->controller)) {

            $injector = (new DI(app()));

            // Load controller
            $object = $injector->instantiate($this->controller, $request);
            if ($object) {

                // Load method
                if (
                    $object
                    && method_exists($object, $this->function)
                    && is_callable(array($object, $this->function))
                ) {

                    $method = new ReflectionMethod($this->controller, $this->function);
                    $result = $injector->processMethodAttributes($method, $request);
                    if (!isset($result)) {
                        $result = $method->invoke(
                            $object,
                            ...array_merge($injector->resolveMethodDependencies(
                                $method,
                                $request,
                            ), $this->params)
                        );
                    }

                    if ($request instanceof RequestInterface) {
                        return $result !== false ?
                            (new ResponseHandler(data: $result, version: $request->version(), format: app()->config->httpResponseFormat))->handle() :
                            throw new NotFoundException("Not found - " . ($request->method() . ' ' . $request->path()));
                    }
                    return $result !== false ?
                        (new ResponseHandler(data: $result, format: app()->config->httpResponseFormat))->handle() :
                        throw new NotFoundException("Resource not found");
                }
                throw new SystemError("Function not found or can't be executed: " . $this->controller . '::' . $this->function);
            }
            throw new SystemError("Failed to instantiate controller: " . $this->controller);
        }
        throw new SystemError("Class does not exist: " . $this->controller);
    }
}
