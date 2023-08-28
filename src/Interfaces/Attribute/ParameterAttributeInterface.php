<?php

namespace Armie\Interfaces\Attribute;

use Armie\App;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;
use ReflectionParameter;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ParameterAttributeInterface
{
    /**
     * @param ReflectionParameter                  $parameter
     * @param T|null                               $value
     * @param App                                  $app
     * @param RequestInterface|RouteInterface|null $request
     *
     * @return T|null
     *
     * @template T
     */
    public function processParameter(ReflectionParameter $parameter, mixed $value = null, App $app, RequestInterface|RouteInterface|null $request = null): mixed;
}
