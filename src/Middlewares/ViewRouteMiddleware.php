<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Handlers\ResponseHandler;
use Busarm\PhpMini\DI;
use Busarm\PhpMini\Enums\ResponseFormat;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\NotFoundException;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

use function Busarm\PhpMini\Helpers\app;
use function Busarm\PhpMini\Helpers\view;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
            $result = app()->di->instantiate($this->viewPathOrClass, $request);
        }
        // View Component
        else {
            $result = view($this->viewPathOrClass, $this->params, true);
        }

        if (!is_null($result)) {
            if ($request instanceof RequestInterface) {
                return $result !== false ?
                    (new ResponseHandler(data: $result, version: $request->version(), format: ResponseFormat::HTML))->handle() :
                    throw new NotFoundException("Not found - " . ($request->method() . ' ' . $request->path()));
            }
            return $result !== false ?
                (new ResponseHandler(data: $result, format: ResponseFormat::HTML))->handle() :
                throw new NotFoundException("Resource not found");
        } else {
            throw new SystemError(sprintf("Route destination view path or class '%s' not found", $this->viewPathOrClass));
        }
    }
}
