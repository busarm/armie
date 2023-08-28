<?php

namespace Armie\Interfaces;

/**
 * Add support for app-wide singleton.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface SingletonInterface
{
    /**
     * Create / Retrieve singleton instance.
     *
     * @param array<string, mixed> $params
     *
     * @return static
     */
    public static function make(array $params = []): static;
}
