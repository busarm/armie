<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Busarm\PhpMini\Interfaces\SessionStoreInterface;
use Closure;
use Psr\Http\Message\UriInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ContainerInterface
{
    /**
     * Add singleton
     * 
     * @param string $className
     * @param object|null $object
     * @return static
     */
    public function addSingleton(string $className, &$object): static;

    /**
     * Get singleton
     *
     * @param string $className
     * @param object $default
     * @return object
     */
    public function getSingleton(string $className, $default = null);
}
