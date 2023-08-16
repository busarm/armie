<?php

namespace Armie\Helpers;

use DateTime;
use Stringable;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class StringableDateTime extends DateTime implements Stringable
{

    /**
     * Gets a string representation of the object
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return $this->format(self::W3C);
    }
}
