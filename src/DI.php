<?php

namespace Busarm\PhpMini;

use Closure;
use Busarm\PhpMini\Errors\DependencyError;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
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
    public final function __construct(protected App $app)
    {
    }

    /**
     * Instantiate class with dependencies
     *
     * @param class-string<T> $class
     * @param RequestInterface|RouteInterface|null $request Request
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return T
     * @template T Item type template
     */
    public function instantiate(string $class, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        // Resolve with custom resolver
        if (!($instance = $this->app->resolver->resolve($class, $request))) {
            if (method_exists($class, '__construct')) {
                if ((new ReflectionMethod($class, '__construct'))->isPublic()) {
                    $instance = new $class(...$this->resolveMethodDependencies($class, '__construct', $request, $params));
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
     * @param RequestInterface|RouteInterface|null $request Request
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public function resolveMethodDependencies(string $class, string $method, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        $reflection = new ReflectionMethod($class, $method);
        // Detect circular dependencies
        $parameters = array_map(fn ($param) => strval($param->getType()), $reflection->getParameters());
        if (in_array($class, $parameters)) {
            throw new DependencyError(sprintf("Circular dependency detected in %s::&s", $class, $method));
        }
        return $this->resolveDependencies($reflection->getParameters(), $request, $params);
    }

    /**
     * Resolve dependendies for class method
     *
     * @param Closure $callable
     * @param RequestInterface|RouteInterface|null $request Request
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public function resolveCallableDependencies(Closure $callable, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        $reflection = new \ReflectionFunction($callable);
        return $this->resolveDependencies($reflection->getParameters(), $request, $params);
    }

    /**
     * Resolve dependendies
     *
     * @param \ReflectionParameter[] $parameters
     * @param RequestInterface|RouteInterface|null $request
     * @param array<string, mixed> $params
     * @return array
     */
    protected function resolveDependencies(array $parameters, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        $paramKeys = array_keys($params);
        foreach ($parameters as $param) {
            if (!in_array($param->getName(), $paramKeys) && ($type = $param->getType()) && ($name = strval($type))) {

                // Resolve with custom resolver
                if (!($instance = $this->app->resolver->resolve($name, $request))) {

                    // If type can be instantiated
                    if ($this->instatiatable($type)) {
                        // Instantiate class for name
                        $instance = $this->instantiate($name, $request);
                    }
                    // If type is an interface - Resolve with interface bindings
                    else if (interface_exists($name)) {
                        if ($className = $this->app->getBinding($name)) {
                            // Instantiate class for name
                            $instance = $this->instantiate($className, $request);
                        }
                        ($param->isOptional() || $param->isDefaultValueAvailable()) or
                            throw new DependencyError("No interface binding exists for " . $name);
                    } else continue;
                }

                // Customize resolution
                if (isset($instance) && $this->app->resolver) {
                    $instance = $this->app->resolver->customize($instance, $request) ?: $instance;
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
    protected function instatiatable($type)
    {
        // Add conditon if something is leftout.
        // This is to ensure that the type is an existing class.
        $name = strval($type);
        return $name != Closure::class && !is_callable($name) && class_exists($name);
    }
}
