<?php

namespace Busarm\PhpMini\Middlewares;

use ArgumentCountError;
use Busarm\PhpMini\App;
use Busarm\PhpMini\DI;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Throwable;
use TypeError;

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
            if ($object) {
                // Load method
                if (
                    $object
                    && method_exists($object, $this->function)
                    && is_callable(array($object, $this->function))
                ) {
                    try {
                        return call_user_func_array(
                            array($object, $this->function),
                            array_merge(DI::resolveMethodDependencies($app, $this->controller, $this->function, function (&$param) use (&$app) {
                                if ($param instanceof BaseDto) {
                                    $param->load($app->request->getRequestList(), true);
                                }
                            }), $this->params)
                        );
                    } catch (TypeError $th) {
                        throw new BadRequestException("Invalid parameter(s): " . $th->getMessage());
                    }
                }
                throw new SystemError("Function not found or can't be executed: " . $this->controller . '::' . $this->function);
            }
            throw new SystemError("Failed to instantiate controller: " . $this->controller);
        }
        throw new SystemError("Class does not exist: " . $this->controller);
    }
}
