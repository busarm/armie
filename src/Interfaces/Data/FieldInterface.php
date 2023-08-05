<?php

namespace Busarm\PhpMini\Interfaces\Data;

use Stringable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface FieldInterface extends  Stringable
{
    /**
     * Get field name
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Get field type
     * 
     * @return string
     */
    public function getType(): string;
}
