<?php

namespace Armie\Interfaces;

/**
 * Add support for request only (stateless) singleton
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface SingletonStatelessInterface
{
    /**
     * Create / Retrieve stateless singleton instance
     *
     * @param RequestInterface|RouteInterface $request
     * @param array<string, mixed> $params
     * @return static
     */
    public static function make(RequestInterface|RouteInterface $request, array $params = []): static;
}