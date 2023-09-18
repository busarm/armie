<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @template T Item type template
 * @codeCoverageIgnore
 */
interface Arrayable
{
    /**
     * @param bool $trim Remove NULL properties
     *
     * @return array<T>
     */
    public function toArray($trim = true): array;
}
