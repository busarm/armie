<?php

namespace Armie\Traits;

use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;

use function Armie\Helpers\app;

/**
 * Create / Retrieve Singletons for stateless requests
 *  
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
    public static function make(RequestInterface|RouteInterface $request, array $params = []): static
    {
        return app()->make(static::class, $params, $request);
    }
}
