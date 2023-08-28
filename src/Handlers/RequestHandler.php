<?php

namespace Armie\Handlers;

use Armie\Interfaces\RequestHandlerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;
use Closure;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class RequestHandler implements RequestHandlerInterface
{
    private Closure $handler;

    public function __construct(callable $handler)
    {
        $this->handler = Closure::fromCallable($handler);
    }

    public function handle(RequestInterface|RouteInterface $request): ResponseInterface
    {
        return ($this->handler)($request);
    }
}
