<?php

namespace Busarm\PhpMini\Interfaces\Attribute;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use ReflectionParameter;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ParameterAttributeInterface
{
    /**
     * @param ReflectionParameter $parameter
     * @param T|null $value
     * @param App $app
     * @param RequestInterface|RouteInterface|null $request
     * @return T|null
     * @template T
     */
    public function processParameter(ReflectionParameter $parameter, mixed $value = null, App $app, RequestInterface|RouteInterface|null $request = null): mixed;
}
