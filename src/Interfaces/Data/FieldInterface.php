<?php

namespace Armie\Interfaces\Data;

use Armie\Enums\DataType;
use Stringable;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface FieldInterface extends Stringable
{
    /**
     * Get field name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get field type.
     *
     * @return DataType
     */
    public function getType(): DataType;
}
