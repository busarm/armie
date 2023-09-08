<?php

namespace Armie\Resolvers;

use Armie\Interfaces\Resolver\HttpConnectionResolver;
use Workerman\Connection\TcpConnection;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class HttpConnection implements HttpConnectionResolver
{
    public function __construct(private TcpConnection $connection)
    {
    }

    /**
     * Get the value of connection.
     */
    public function get(): ?TcpConnection
    {
        return $this->connection;
    }
}
