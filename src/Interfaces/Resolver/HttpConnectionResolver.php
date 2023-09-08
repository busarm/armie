<?php

namespace Armie\Interfaces\Resolver;

use Workerman\Connection\TcpConnection;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface HttpConnectionResolver
{
    /**
     * Get server connection.
     *
     * @return ?TcpConnection
     */
    public function get(): ?TcpConnection;
}
