<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Interfaces\SessionStoreInterface;
use Busarm\PhpMini\Response;
use Psr\Http\Server\MiddlewareInterface as ServerMiddlewareInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionStoreInterface|null $store = null)
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
            if ($this->store) $request->setSession($this->store);
            $request->session()?->start();
        }
        return $handler->handle($request);
    }
}
