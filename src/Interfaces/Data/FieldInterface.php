<?php

namespace Armie\Interfaces\Data;

use Stringable;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
