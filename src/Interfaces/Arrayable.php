<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface Arrayable
{
    /**
     * @param bool $trim Remove NULL properties
     *
     * @return array
     */
    public function toArray($trim = true): array;
}
