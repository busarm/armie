<?php

namespace Busarm\PhpMini\Middlewares;

use Closure;
use Busarm\PhpMini\App;
use Busarm\PhpMini\DI;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use TypeError;

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
            try {
                return ($this->callable)(...array_merge(DI::resolveCallableDependencies($app, $this->callable, function (&$param) use (&$app) {
                    if ($param instanceof BaseDto) {
                        $param->load($app->request->getRequestList(), true);
                    }
                }), $this->params));
            } catch (TypeError $th) {
                throw new BadRequestException("Invalid parameter(s): " . $th->getMessage());
            }
        }
        throw new SystemError("Callable route can't be executed");
    }
}
