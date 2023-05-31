<?php

namespace Busarm\PhpMini\Interfaces\Attribute;

use ReflectionProperty;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface PropertyAttributeInterface
{
    /**
     * @param ReflectionProperty $property
     * @param T|null $value
     * @return T|null
     * @template T
     */
    public function processProperty(ReflectionProperty $property, mixed $value = null): mixed;
}
