<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * Add support for app-wide singleton
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface SingletonInterface
{
    /**
     * Create / Retrieve singleton instance 
     *
     * @param array<string, mixed> $params
     * @return static
     */
    public static function make(array $params = []): static;
}
