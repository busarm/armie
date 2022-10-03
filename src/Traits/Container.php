<?php

namespace Busarm\PhpMini\Traits;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 03/10/2022
 * Time: 15:38 PM
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
