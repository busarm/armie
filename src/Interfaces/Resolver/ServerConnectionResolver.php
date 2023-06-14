<?php

namespace Busarm\PhpMini\Interfaces\Resolver;

use Workerman\Connection\ConnectionInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ServerConnectionResolver
{
    /**
     * Get server connection
     * 
     * @return ?ConnectionInterface
     */
    public function getConnection(): ?ConnectionInterface;
}
