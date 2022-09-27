<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface MiddlewareInterface
{
    /**
     * Middleware handler
     *
     * @param RequestInterface|RouteInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return false|mixed Return `false` if failed
     */
    public function handle(RequestInterface|RouteInterface &$request, ResponseInterface &$response, callable $next = null): mixed;
}