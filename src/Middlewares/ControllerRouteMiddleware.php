<?php

namespace Busarm\PhpMini\Middlewares;

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
final class ControllerRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private $controller, private $function, private $params = [])
    {
    }

    public function handle(App $app, callable $next = null): mixed
    {
        if (class_exists($this->controller)) {
            // Load controller
            $object = $app->make($this->controller);
            if (
                // Load method
                method_exists($object, $this->function)
                && is_callable(array($object, $this->function))
            ) {
                return call_user_func_array(
                    array($object, $this->function),
                    array_merge(DI::resolveMethodDependencies($app, $this->controller, $this->function), $this->params)
                );
            }
            throw new SystemError("Function not found or can't be executed: " . $this->controller . '::' . $this->function);
        }
        throw new SystemError("Class does not exist: " . $this->controller);
    }
}
