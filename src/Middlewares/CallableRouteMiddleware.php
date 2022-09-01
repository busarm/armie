<?php

namespace Busarm\PhpMini\Middlewares;

use Closure;
use Busarm\PhpMini\App;
use Busarm\PhpMini\DI;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\HttpException;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class CallableRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private Closure $callable, private $params = [])
    {
    }

    public function handle(App $app, callable $next = null): mixed
    {
        if (is_callable($this->callable)) {
            return ($this->callable)(...array_merge(DI::resolveCallableDependencies($app, $this->callable), $this->params));
        }
        throw new SystemError("Callable route can't be executed");
    }
}
