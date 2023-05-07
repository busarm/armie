<?php

namespace Busarm\PhpMini;

use Closure;
use Busarm\PhpMini\Errors\DependencyError;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
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
     * @param class-string<T> $class
     * @param DependencyResolverInterface|null $resolver Custom resolver to extend class resolution
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return T
     * @template T Item type template
     */
    public static function instantiate(string $class, DependencyResolverInterface|null $resolver = null, array $params = [])
    {
        // Resolve with custom resolver
        if (!($instance = self::processResolver($class, $resolver))) {
            if (method_exists($class, '__construct')) {
                if ((new ReflectionMethod($class, '__construct'))->isPublic()) {
                    $instance = new $class(...self::resolveMethodDependencies($class, '__construct', $resolver, $params));
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
     * @param DependencyResolverInterface|null $resolver Custom resolver to extend class resolution
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public static function resolveMethodDependencies(string $class, string $method, DependencyResolverInterface|null $resolver = null, array $params = [])
    {
        $reflection = new ReflectionMethod($class, $method);
        // Detect circular dependencies
        $parameters = array_map(fn ($param) => strval($param->getType()), $reflection->getParameters());
        if (in_array($class, $parameters)) {
            throw new DependencyError("Circular dependency detected in " . $class);
        }
        return self::resolveDependencies($reflection->getParameters(), $resolver, $params);
    }

    /**
     * Resolve dependendies for class method
     *
     * @param Closure $callable
     * @param DependencyResolverInterface|null $resolver Custom resolver to extend class resolution
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public static function resolveCallableDependencies(Closure $callable, DependencyResolverInterface|null $resolver = null, array $params = [])
    {
        $reflection = new \ReflectionFunction($callable);
        return self::resolveDependencies($reflection->getParameters(), $resolver, $params);
    }

    /**
     * Resolve dependendies
     *
     * @param \ReflectionParameter[] $parameters
     * @param DependencyResolverInterface|null $resolver
     * @param array<string, mixed> $params
     * @return array
     */
    protected static function resolveDependencies(array $parameters, DependencyResolverInterface|null $resolver = null, array $params = [])
    {
        $paramKeys = array_keys($params);
        foreach ($parameters as $param) {
            if (!in_array($param->getName(), $paramKeys) && ($type = $param->getType()) && ($name = strval($type))) {

                // Resolve with custom resolver
                if (!($instance = self::processResolver($name, $resolver))) {

                    // If type can be instantiated
                    if (self::instatiatable($type)) {
                        // Instantiate class for name
                        $instance = self::instantiate($name, $resolver);
                    }
                    // If type is an interface - Resolve with interface bindings
                    else if (interface_exists($name)) {
                        if ($className = app()->getBinding($name)) {
                            // Instantiate class for name
                            $instance = self::instantiate($className, $resolver);
                        }
                        ($param->isOptional() || $param->isDefaultValueAvailable()) or
                            throw new DependencyError("No interface binding exists for " . $name);
                    } else continue;
                }

                // Customize resolution
                if (isset($instance) && $resolver) {
                    $instance = $resolver->customizeDependency($instance) ?: $instance;
                }

                $params[$param->getName()] = $instance;
            }
        }
        return $params;
    }

    /**
     * Process dependecy resolver
     *
     * @param class-string<T> $class
     * @param DependencyResolverInterface|null $resolver
     * @return T
     * @template T Item type template
     */
    protected static function processResolver(string $class, DependencyResolverInterface|null $resolver = null)
    {
        return $resolver ? $resolver->resolveDependency($class) : null;
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
        $name = strval($type);
        return $name != Closure::class && !is_callable($name) && class_exists($name);
    }
}
