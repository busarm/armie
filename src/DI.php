<?php

namespace Busarm\PhpMini;

use Closure;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Errors\DependencyError;
use ReflectionMethod;

/**
 * PHP Mini Framework
 *
 * Dependency Injector
 * 
 * @source https://www.php.net/manual/en/reflectionnamedtype.getname.php#122909
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class DI
{
    private final function __construct()
    {
    }

    /**
     * Instantiate class with dependencies
     *
     * @param App $app
     * @param string $class
     * @return object
     */
    public static function instantiate(App $app, $class)
    {
        if ($resolver = $app->getResolver($class)) $instance = $resolver();
        else if (method_exists($class, '__construct')) {
            if ((new ReflectionMethod($class, '__construct'))->isPublic()) $instance = new $class(...self::resolveMethodDependencies($app, $class, '__construct'));
            else throw new DependencyError("Failed to instantiate non-public constructor for class " . $class);
        } else $instance = new $class;
        return $instance;
    }

    /**
     * Resolve dependendies for class method
     *
     * @param App $app
     * @param string $class
     * @param string $method
     * @return array
     */
    public static function resolveMethodDependencies(App $app, $class, $method)
    {
        $reflection = new \ReflectionMethod($class, $method);
        return self::resolveDependencies($app, $reflection->getParameters());
    }

    /**
     * Resolve dependendies for class method
     *
     * @param App $app
     * @param Closure $callable
     * @return array
     */
    public static function resolveCallableDependencies(App $app, Closure $callable)
    {
        $reflection = new \ReflectionFunction($callable);
        return self::resolveDependencies($app, $reflection->getParameters());
    }

    /**
     * Resolve dependendies
     *
     * @param App $app
     * @param ReflectionParameter[] $parameters
     * @return array
     */
    protected static function resolveDependencies(App $app, array $parameters)
    {
        $params = [];
        foreach ($parameters as $param) {
            if ($type = $param->getType()) {
                $instance = NULL;
                // If type is an interface - Get app interface binding
                if (interface_exists($type->getName())) {
                    if ($resolver = $app->getResolver($type->getName())) {
                        $instance = $resolver();
                    } else if (!($className = $app->getBinding($type->getName()))) {
                        throw new DependencyError("No interface binding exists for " . $type->getName());
                    }
                }
                // If type can't be instantiated (e.g scalar types) - skip loop
                else if (!$type || !self::instatiatable($type)) continue;
                // Get class name
                else $className = $type->getName();
                // Resolve dependencies for type
                $instance = $instance ?? self::instantiate($app, $className);
                // If type is an Request Dto - Parse request
                if ($instance instanceof BaseDto) {
                    $instance->load($app->request->getRequestList(), true);
                }
                $params[$param->getName()] = $instance;
            }
        }
        return $params;
    }

    /**
     * Check if type can be instantiated
     *
     * @param ReflectionType|ReflectionNamedType|string $type
     * @return bool
     */
    protected static function instatiatable($type)
    {
        // Add conditon if something is leftout.
        // This is to ensure that the type is an existing class.
        return $type != Closure::class && !is_callable($type) && class_exists($type);
    }
}
