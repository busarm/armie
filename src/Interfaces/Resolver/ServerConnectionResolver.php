<?php

namespace Armie\Interfaces\Resolver;

use Workerman\Connection\ConnectionInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ServerConnectionResolver
{
    /**
     * Get server connection.
     *
     * @return ?ConnectionInterface
     */
    public function getConnection(): ?ConnectionInterface;
}
