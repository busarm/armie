<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\Bags\AttributeBag;
use Busarm\PhpMini\Interfaces\Bags\SessionBag;
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
     * @return self
     */
    public function addSingleton(string $className, &$object);

    /**
     * Get singleton
     *
     * @param string $className
     * @param object $default
     * @return self
     */
    public function getSingleton(string $className, $default = null);
}
