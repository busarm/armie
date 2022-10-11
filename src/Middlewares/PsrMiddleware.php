<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Handlers\RequestHandler;
use Busarm\PhpMini\Handlers\ServerRequestHandler;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Response;
use Psr\Http\Server\MiddlewareInterface as ServerMiddlewareInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class PsrMiddleware implements MiddlewareInterface
{
    public function __construct(private ServerMiddlewareInterface $psr)
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
        if ($request instanceof RequestInterface) {
            return Response::fromPsr($this->psr->process($request->toPsr(), new ServerRequestHandler($handler)));
        }
        return $handler->handle($request);
    }
}
