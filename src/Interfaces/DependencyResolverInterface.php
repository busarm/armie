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
     * @param string $className
     * @return mixed
     */
    public function resolveDependency(string $className): mixed;

    /**
     * Customize dependency
     *
     * @param mixed $instance
     * @return mixed
     */
    public function customizeDependency(mixed &$instance): mixed;
}