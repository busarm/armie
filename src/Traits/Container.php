<?php

namespace Busarm\PhpMini\Traits;

/**
 * Manage Singletons
 *  
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
trait Container
{
    private array $singletons   =   [];

    /**
     * Add singleton
     * 
     * @param string $className
     * @param object|null $object
     * @return self
     */
    public function addSingleton(string $className, &$object)
    {
        $this->singletons[$className] = $object;
        return $this;
    }

    /**
     * Get singleton
     *
     * @param string $className
     * @param object $default
     * @return self
     */
    public function getSingleton(string $className, $default = null)
    {
        return $this->singletons[$className] ?? $default;
    }
}
