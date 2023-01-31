<?php

namespace Busarm\PhpMini\Traits;

use function Busarm\PhpMini\Helpers\app;

/**
 * Create / Retrieve Singletons
 *  
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
trait Singleton
{
    /**
     * Create / Retrieve singleton instance 
     *
     * @param array $params
     * @return static
     */
    public static function make(array $params = []): self
    {
        return app()->make(static::class, $params);
    }
}
