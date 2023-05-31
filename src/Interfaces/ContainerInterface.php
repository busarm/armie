<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\App;
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
     * @param class-string<T> $className
     * @param T|null $object
     * @return static
     * @template T Item type template
     */
    public function addSingleton(string $className, &$object): static;

    /**
     * Get singleton
     *
     * @param class-string<T> $className
     * @param T|null $default
     * @return T
     * @template T Item type template
     */
    public function getSingleton(string $className, $default = null);
}
