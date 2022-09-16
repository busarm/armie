<?php

namespace Busarm\PhpMini;

use Closure;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Errors\DependencyError;
use ReflectionMethod;

use function Busarm\PhpMini\Helpers\log_debug;

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
     * @param Closure|null $callback Custom callback to customize resolution.  e.g fn(&$instance) => $instance->load(...)
     * @return object
     */
    public static function instantiate(App $app, $class, Closure|null $callback = null)
    {
        if ($resolver = $app->getResolver($class)) $instance = $resolver();
        else if (method_exists($class, '__construct')) {
            if ((new ReflectionMethod($class, '__construct'))->isPublic()) {
                $instance = new $class(...self::resolveMethodDependencies($app, $class, '__construct', $callback));
            } else throw new DependencyError("Failed to instantiate non-public constructor for class " . $class);
        } else $instance = new $class;
        return $instance;
    }

    /**
     * Resolve dependendies for class method
     *
     * @param App $app
     * @param string $class
     * @param string $method
     * @param Closure|null $callback Custom callback to customize resolution.  e.g fn(&$instance) => $instance->load(...)
     * @return array
     */
    public static function resolveMethodDependencies(App $app, string $class, string $method, Closure|null $callback = null)
    {
        $reflection = new \ReflectionMethod($class, $method);
        // Detect circular dependencies
        $params = array_map(fn ($param) => strval($param->getType()) ?: ($param->getType()?->getName()), $reflection->getParameters());
        if (in_array($class, $params)) {
            throw new DependencyError("Circular dependency detected in " . $class);
        }
        return self::resolveDependencies($app, $reflection->getParameters(), $callback);
    }

    /**
     * Resolve dependendies for class method
     *
     * @param App $app
     * @param Closure $callable
     * @param Closure|null $callback Custom callback to customize resolution. e.g fn(&$instance) => $instance->load(...)
     * @return array
     */
    public static function resolveCallableDependencies(App $app, Closure $callable, Closure|null $callback = null)
    {
        $reflection = new \ReflectionFunction($callable);
        return self::resolveDependencies($app, $reflection->getParameters(), $callback);
    }

    /**
     * Resolve dependendies
     *
     * @param App $app
     * @param ReflectionParameter[] $parameters
     * @param Closure|null $callback
     * @return array
     */
    protected static function resolveDependencies(App $app, array $parameters, Closure|null $callback = null)
    {
        $params = [];
        foreach ($parameters as $param) {
            if (($type = $param->getType()) && ($name = strval($type) ?: $type?->getName())) {
                // Resolve with app resolvers
                if ($resolver = $app->getResolver($name)) {
                    $instance = $resolver();
                }
                // If type can be instantiated
                else if (self::instatiatable($type)) {
                    // Instantiate class for name
                    $instance = self::instantiate($app, $name, $callback);
                }
                // If type is an interface - Resolve with interface bindings
                else if (interface_exists($name)) {
                    if ($className = $app->getBinding($name)) {
                        // Instantiate class for name
                        $instance = self::instantiate($app, $className, $callback);
                    }
                    throw new DependencyError("No interface binding exists for " . $name);
                } else continue;

                // Trigger callback if available
                if (isset($instance) && $callback) {
                    $instance = $callback($instance) ?: $instance;
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
        $name = strval($type) ?: $type->getName();
        return $name != Closure::class && !is_callable($name) && class_exists($name);
    }
}
