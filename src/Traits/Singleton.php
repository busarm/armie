<?php

namespace Armie\Traits;

use function Armie\Helpers\app;

/**
 * Create / Retrieve Singletons.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
trait Singleton
{
    /**
     * Create / Retrieve singleton instance.
     *
     * @param array $params
     *
     * @return static
     */
    public static function make(array $params = []): static
    {
        return app()->make(static::class, $params);
    }
}
