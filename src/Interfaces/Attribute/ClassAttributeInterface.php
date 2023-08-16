<?php

namespace Armie\Interfaces\Attribute;

use Armie\App;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;
use ReflectionClass;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ClassAttributeInterface
{
    /**
     * @param ReflectionClass $class
     * @param App $app
     * @param RequestInterface|RouteInterface|null $request
     * @return void
     */
    public function processClass(ReflectionClass $class, App $app, RequestInterface|RouteInterface|null $request = null): void;
}
