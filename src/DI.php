<?php

namespace Busarm\PhpMini;

use Closure;
use Busarm\PhpMini\Errors\DependencyError;
use Busarm\PhpMini\Interfaces\Attribute\ClassAttributeInterface;
use Busarm\PhpMini\Interfaces\Attribute\FunctionAttributeInterface;
use Busarm\PhpMini\Interfaces\Attribute\MethodAttributeInterface;
use Busarm\PhpMini\Interfaces\Attribute\ParameterAttributeInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

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
     * @param ReflectionClass|class-string<T> $class
     * @param RequestInterface|RouteInterface|null $request Request
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return T
     * @template T Item type template
     */
    public function instantiate(ReflectionClass|string $class, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        if (!($class instanceof ReflectionClass)) {
            $class = new ReflectionClass($class);
            $this->processClassAttributes(new ReflectionClass($class->getName()), $request);
        }

        // Resolve with custom resolver
        if (
            !(($instance = $this->app->resolver->resolve($class->getName(), $request))
                || ($class->getParentClass()
                    && ($instance = $this->app->resolver->resolve($class->getParentClass()->getName(), $request))))
            && $class->isInstantiable()
        ) {
            // Resolve constructor method if available
            if (method_exists($class->getName(), '__construct')) {
                $method = new ReflectionMethod($class->getName(), '__construct');
                $this->processMethodAttributes($method, $request);

                $instance = $class->newInstance(...$this->resolveMethodDependencies($method, $request, $params));
            } else {
                $instance = $class->newInstance();
            }
        }
        return $instance;
    }

    /**
     * Resolve dependendies for class method
     *
     * @param ReflectionMethod $method
     * @param RequestInterface|RouteInterface|null $request Request
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public function resolveMethodDependencies(ReflectionMethod $method, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        // Detect circular dependencies
        $parameters = array_filter($method->getParameters(), fn ($param) => strval($param->getType()) == $method->getDeclaringClass()->getName());
        if (!empty($parameters)) {
            throw new DependencyError(sprintf("Circular dependency detected in %s::%s", $method->getDeclaringClass()->getName(), $method));
        }

        return $this->resolveDependencies($method->getParameters(), $request, $params);
    }

    /**
     * Resolve dependendies for class method
     *
     * @param ReflectionFunction|Closure $callable
     * @param RequestInterface|RouteInterface|null $request Request
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @return array
     */
    public function resolveCallableDependencies(ReflectionFunction|Closure $callable, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        if (!($callable instanceof ReflectionFunction)) {
            $callable = new ReflectionFunction($callable);
            $this->processFunctionAttributes($callable, $request);
        }

        // Detect circular dependencies
        if ($callable->getClosureScopeClass()) {
            $parameters = array_filter($callable->getParameters(), fn ($param) => strval($param->getType()) == $callable->getClosureScopeClass()->getName());
            if (!empty($parameters)) {
                throw new DependencyError(sprintf("Circular dependency detected in %s::%s", $callable->getClosureScopeClass()->getName(), $callable));
            }
        }

        return $this->resolveDependencies($callable->getParameters(), $request, $params);
    }

    /**
     * Resolve dependendies
     *
     * @param ReflectionParameter[] $parameters
     * @param RequestInterface|RouteInterface|null $request
     * @param array<string, mixed> $params
     * @return array
     */
    protected function resolveDependencies(array $parameters, RequestInterface|RouteInterface|null $request = null, array $params = [])
    {
        $paramKeys = array_keys($params);
        foreach ($parameters as $param) {
            if (!in_array($param->getName(), $paramKeys)) {

                $instance = NULL;

                // Resolve with custom resolver
                if (($type = $param->getType())
                    && ($name = strval($type))
                    && is_null($instance = $this->app->resolver->resolve($name, $request))
                ) {

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

                // Process attributes if available
                $instance = $this->processParameterAttributes($param, $instance, $request);

                $params[$param->getName()] = $instance ?? ($param->isOptional() ? $param->getDefaultValue() : null);
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

    /**
     * Process Class Attributes
     *
     * @param ReflectionClass $class
     * @param RequestInterface|RouteInterface|null $request
     * @return void
     */
    public function processClassAttributes(ReflectionClass $class, RequestInterface|RouteInterface|null $request = null)
    {
        foreach ($class->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof ClassAttributeInterface) {
                $instance->processClass($class, $this->app, $request);
            }
        }
    }

    /**
     * Process Method Attributes
     *
     * @param ReflectionMethod $method
     * @param RequestInterface|RouteInterface|null $request
     * @return mixed
     */
    public function processMethodAttributes(ReflectionMethod $method, RequestInterface|RouteInterface|null $request = null): mixed
    {

        $result = null;
        foreach ($method->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof MethodAttributeInterface) {
                $result = $instance->processMethod($method, $this->app, $request);
            }
        }
        return $result;
    }

    /**
     * Process Callable Attributes
     *
     * @param ReflectionFunction $function
     * @param RequestInterface|RouteInterface|null $request
     * @return mixed
     */
    public function processFunctionAttributes(ReflectionFunction $function, RequestInterface|RouteInterface|null $request = null): mixed
    {
        $result = null;
        foreach ($function->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof FunctionAttributeInterface) {
                $result = $instance->processFunction($function, $this->app, $request);
            }
        }
        return $result;
    }

    /**
     * Process Parameter Attributes
     *
     * @param ReflectionParameter $parameter
     * @param T|null $value
     * @param RequestInterface|RouteInterface|null $request
     * @return T|null
     * @template T
     */
    public function processParameterAttributes(ReflectionParameter $parameter, mixed $value = null, RequestInterface|RouteInterface|null $request = null)
    {
        $result = $value;
        foreach ($parameter->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof ParameterAttributeInterface) {
                $result = $instance->processParameter($parameter, $value, $this->app, $request) ?? $result;
            }
        }
        return $result;
    }
}
