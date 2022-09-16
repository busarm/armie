<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface Arrayable
{
    /**
     * @param bool $trim Remove NULL properties
     * @return array
     */
    public function toArray($trim = true): array;
}
