<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\App;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface MiddlewareInterface
{
    /**
     * Middleware handler
     *
     * @param App $app
     * @param Callable|null $next
     * @return mixed
     */
    public function handle(App $app, Callable $next = null): mixed;
}