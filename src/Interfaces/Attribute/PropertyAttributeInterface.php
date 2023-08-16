<?php

namespace Armie\Interfaces\Attribute;

use ReflectionProperty;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
