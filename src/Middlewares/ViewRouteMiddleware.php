<?php

namespace Armie\Middlewares;

use Armie\Handlers\ResponseHandler;
use Armie\DI;
use Armie\Enums\ResponseFormat;
use Armie\Errors\SystemError;
use Armie\Exceptions\NotFoundException;
use Armie\Interfaces\MiddlewareInterface;
use Armie\Interfaces\RequestHandlerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;

use function Armie\Helpers\app;
use function Armie\Helpers\view;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class ViewRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private string $viewPathOrClass, private $params = [])
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
        // View Class
        if (class_exists($this->viewPathOrClass)) {
            $result = app()->di->instantiate($this->viewPathOrClass, $request, $this->params);
        }
        // View Component
        else {
            $result = view($this->viewPathOrClass, $this->params, true);
        }

        if (!is_null($result)) {
            if ($request instanceof RequestInterface) {
                return $result !== false ?
                    (new ResponseHandler(data: $result, version: $request->version(), format: ResponseFormat::HTML))->handle() :
                    throw new NotFoundException("Not found - " . ($request->method()->value . ' ' . $request->path()));
            }
            return $result !== false ?
                (new ResponseHandler(data: $result, format: ResponseFormat::HTML))->handle() :
                throw new NotFoundException("Resource not found");
        } else {
            throw new SystemError(sprintf("Route destination view path or class '%s' not found", $this->viewPathOrClass));
        }
    }
}
