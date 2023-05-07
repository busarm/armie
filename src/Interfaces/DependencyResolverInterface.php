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
     * @return T
     * @template T Item type template
     */
    public function resolveDependency(string $className): mixed;

    /**
     * Customize dependency
     *
     * @param T $instance
     * @return T
     * @template T Item type template
     */
    public function customizeDependency(mixed &$instance): mixed;
}
