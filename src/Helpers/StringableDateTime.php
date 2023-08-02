<?php

namespace Busarm\PhpMini\Helpers;

use DateTime;
use Stringable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
