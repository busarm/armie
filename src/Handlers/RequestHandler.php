<?php

namespace Busarm\PhpMini\Handlers;

use Closure;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
