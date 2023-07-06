<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * Add support for app-wide singleton
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface DependencyResolverInterface
{
    /**
     * Resolve dependency for class name
     *
     * @param class-string<T> $className
     * @param RequestInterface|RouteInterface $request
     * @return T
     * @template T Item type template
     */
    public function resolve(string $className, RequestInterface|RouteInterface|null $request = null): mixed;

    /**
     * Customize dependency
     *
     * @param T $instance
     * @param RequestInterface|RouteInterface $request
     * @return T|mixed
     * @template T Item type template
     */
    public function customize(mixed $instance, RequestInterface|RouteInterface|null $request = null): mixed;
}
