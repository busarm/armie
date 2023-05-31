<?php

namespace Busarm\PhpMini\Interfaces\Attribute;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use ReflectionClass;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
