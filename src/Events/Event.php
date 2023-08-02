<?php

namespace Busarm\PhpMini\Events;

use function Busarm\PhpMini\Helpers\dispatch;
use function Busarm\PhpMini\Helpers\listen;

/**
 * Handle event operations
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class Event
{
    /**
     * @inheritDoc
     */
    public static function listen(callable|string $listner)
    {
        listen(static::class, $listner);
    }

    /**
     * @inheritDoc
     */
    public static function dispatch(array $data = [])
    {
        dispatch(static::class, $data);
    }
}
