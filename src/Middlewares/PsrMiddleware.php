<?php

namespace Armie\Middlewares;

use Armie\Config;
use Armie\Handlers\PsrServerRequestHandler;
use Armie\Interfaces\MiddlewareInterface;
use Armie\Interfaces\RequestHandlerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;
use Armie\Response;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class PsrMiddleware implements MiddlewareInterface
{
    public function __construct(private PsrMiddlewareInterface $psr, private Config|null $config = null)
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
            return Response::fromPsr($this->psr->process($request->toPsr(), new PsrServerRequestHandler($handler, $this->config)));
        }
        return $handler->handle($request);
    }
}
