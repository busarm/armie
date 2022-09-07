<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\App;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface MiddlewareInterface
{
    /**
     * Middleware handler
     *
     * @param App $app
     * @param Callable|null $next
     * @return boolean|mixed Return `false` if failed
     */
    public function handle(App $app, callable $next = null): mixed;
}