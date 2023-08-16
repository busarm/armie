<?php

namespace Armie\Traits;

/**
 * Manage Singletons
 *  
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
trait Container
{
    private array $singletons   =   [];

    /**
     * Add singleton
     * 
     * @param class-string<T> $className
     * @param T|null $object
     * @return static
     * @template T Item type template
     */
    public function addSingleton(string $className, &$object): static
    {
        $this->singletons[$className] = &$object;
        return $this;
    }

    /**
     * Get singleton
     *
     * @param class-string<T> $className
     * @param T|null $default
     * @return T|null
     * @template T Item type template
     */
    public function getSingleton(string $className, $default = null)
    {
        return $this->singletons[$className] ?? $default;
    }
}
