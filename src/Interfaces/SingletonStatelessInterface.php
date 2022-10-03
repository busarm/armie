<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * Add support for request only (stateless) singleton
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface SingletonStatelessInterface
{
    /**
     * Create / Retrieve stateless singleton instance
     *
     * @param RequestInterface $request
     * @param array $params
     * @return static
     */
    public static function make(RequestInterface|RouteInterface $request, ResponseInterface $response, array $params = []): static;
}