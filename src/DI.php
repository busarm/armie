<?php

namespace Busarm\PhpMini;

use Closure;
use Busarm\PhpMini\Errors\DependencyError;
use ReflectionMethod;

use function Busarm\PhpMini\Helpers\app;
/**
 * Dependency Injector
 * 
 * PHP Mini Framework
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
     * @param string $class
     * @param Closure|null $resolver Custom resolver to extend class resolution.  e.g `fn($class) => $class == MyCustomClass::class ? MyCustomClass::init() : null`
     * @param Closure|null $callback Custom callback to customize resolution.  e.g 'fn(&$instance) => $instance->load(...)`
     * @param array $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return object
     */
    public static function instantiate(string $class, Closure|null $resolver = null, Closure|null $callback = null, array $params = [])
    {
        // Resolve with custom resolver
        if (!$resolver || !($instance = $resolver($class))) {
            // Resolve with app resolver
            if ($resolver = app()->getResolver($class)) $instance = $resolver();
            else if (method_exists($class, '__construct')) {
                if ((new ReflectionMethod($class, '__construct'))->isPublic()) {
                    $instance = new $class(...self::resolveMethodDependencies($class, '__construct', $resolver, $callback, $params));
                } else throw new DependencyError("Failed to instantiate non-public constructor for class " . $class);
            } else $instance = new $class;
        }
        return $instance;
    }

    /**
     * Resolve dependendies for class method
     *
     * @param string $class
     * @param string $method
     * @param Closure|null $resolver Custom resolver to extend class resolution.  e.g `fn($class) => $class == MyCustomClass::class ? MyCustomClass::init() : null`
     * @param Closure|null $callback Custom callback to customize resolution.  e.g 'fn(&$instance) => $instance->load(...)`
     * @param array $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public static function resolveMethodDependencies(string $class, string $method, Closure|null $resolver = null, Closure|null $callback = null, array $params = [])
    {
        $reflection = new ReflectionMethod($class, $method);
        // Detect circular dependencies
        $parameters = array_map(fn ($param) => strval($param->getType()) ?: ($param->getType()?->getName()), $reflection->getParameters());
        if (in_array($class, $parameters)) {
            throw new DependencyError("Circular dependency detected in " . $class);
        }
        return self::resolveDependencies($reflection->getParameters(), $resolver, $callback, $params);
    }

    /**
     * Resolve dependendies for class method
     *
     * @param Closure $callable
     * @param Closure|null $resolver Custom resolver to extend class resolution.  e.g `fn($class) => $class == MyCustomClass::class ? MyCustomClass::init() : null`
     * @param Closure|null $callback Custom callback to customize resolution.  e.g 'fn(&$instance) => $instance->load(...)`
     * @param array $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public static function resolveCallableDependencies(Closure $callable, Closure|null $resolver = null, Closure|null $callback = null, array $params = [])
    {
        $reflection = new \ReflectionFunction($callable);
        return self::resolveDependencies($reflection->getParameters(), $resolver, $callback, $params);
    }

    /**
     * Resolve dependendies
     *
     * @param \ReflectionParameter $parameters
     * @param Closure|null $resolver
     * @param Closure|null $callback
     * @param array $params
     * @return array
     */
    protected static function resolveDependencies(array $parameters, Closure|null $resolver = null, Closure|null $callback = null, array $params = [])
    {
        $params = $params ?? [];
        $paramKeys = array_keys($params);

        foreach ($parameters as $param) {
            if (!in_array($param->getName(), $paramKeys) && ($type = $param->getType()) && ($name = strval($type) ?: $type?->getName())) {

                // Resolve with custom resolver
                if (!$resolver || !($instance = $resolver($name))) {

                    // Resolve with app resolvers
                    if ($resolver = app()->getResolver($name)) {
                        $instance = $resolver();
                    }
                    // If type can be instantiated
                    else if (self::instatiatable($type)) {
                        // Instantiate class for name
                        $instance = self::instantiate($name, $callback);
                    }
                    // If type is an interface - Resolve with interface bindings
                    else if (interface_exists($name)) {
                        if ($className = app()->getBinding($name)) {
                            // Instantiate class for name
                            $instance = self::instantiate($className, $callback);
                        }
                        throw new DependencyError("No interface binding exists for " . $name);
                    } else continue;
                }

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
     * @param \ReflectionType|\ReflectionNamedType|string $type
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
