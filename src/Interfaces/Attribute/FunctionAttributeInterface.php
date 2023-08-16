<?php

namespace Armie\Interfaces\Attribute;

use Armie\App;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;
use ReflectionFunction;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface FunctionAttributeInterface
{
    /**
     * @param ReflectionFunction $function
     * @param App $app
     * @param RequestInterface|RouteInterface|null $request
     * @return mixed
     */
    public function processFunction(ReflectionFunction $function, App $app, RequestInterface|RouteInterface|null $request = null): mixed;
}
