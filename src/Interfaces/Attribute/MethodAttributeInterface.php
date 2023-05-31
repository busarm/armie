<?php

namespace Busarm\PhpMini\Interfaces\Attribute;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use ReflectionMethod;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface MethodAttributeInterface
{
    /**
     * @param ReflectionMethod $method
     * @param App $app
     * @param RequestInterface|RouteInterface|null $request
     * @return mixed
     */
    public function processMethod(ReflectionMethod $method, App $app, RequestInterface|RouteInterface|null $request = null): mixed;
}
