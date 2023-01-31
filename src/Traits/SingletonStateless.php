<?php

namespace Busarm\PhpMini\Traits;

use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

use function Busarm\PhpMini\Helpers\app;

/**
 * Create / Retrieve Singletons for stateless requests
 *  
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
trait SingletonStateless
{
    /**
     * Create / Retrieve stateless singleton instance
     *
     * @param RequestInterface|RouteInterface $request
     * @param array $params
     * @return static
     */
    public static function make(RequestInterface|RouteInterface $request, array $params = []): self
    {
        return app()->make(static::class, $params, $request);
    }
}
