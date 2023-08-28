<?php

namespace Armie\Middlewares;

use Armie\Config;
use Armie\Interfaces\MiddlewareInterface;
use Armie\Interfaces\RequestHandlerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;
use Armie\Interfaces\SessionStoreInterface;
use SessionHandlerInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private Config $config, private SessionStoreInterface|SessionHandlerInterface|null $session = null)
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
        if ($request instanceof RequestInterface && $this->config->sessionEnabled) {
            if ($this->session) {
                if ($this->session instanceof SessionStoreInterface) {
                    $request->setSession($this->session);
                } else {
                    $request->session()->setHandler($this->session);
                }
            }

            if (!$request->session()->isStarted()) {
                $request->session()->start();
            }
        }

        return $handler->handle($request);
    }
}
