<?php

namespace Armie\Interfaces\Attribute;

use Armie\App;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;
use ReflectionMethod;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface MethodAttributeInterface
{
    /**
     * @param ReflectionMethod                     $method
     * @param App                                  $app
     * @param RequestInterface|RouteInterface|null $request
     *
     * @return mixed
     */
    public function processMethod(ReflectionMethod $method, App $app, RequestInterface|RouteInterface|null $request = null): mixed;
}
