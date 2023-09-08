<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface SingletonContainerInterface
{
    /**
     * Add singleton.
     *
     * @param class-string<T> $className
     * @param T|null          $object
     *
     * @return static
     *
     * @template T Item type template
     */
    public function addSingleton(string $className, &$object): static;

    /**
     * Get singleton.
     *
     * @param class-string<T> $className
     * @param T|null          $default
     *
     * @return T
     *
     * @template T Item type template
     */
    public function getSingleton(string $className, $default = null);
}
