<?php

namespace Busarm\PhpMini\Resolvers;

use Busarm\PhpMini\Interfaces\Resolver\ServerConnectionResolver;
use Workerman\Connection\ConnectionInterface;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ServerConnection implements ServerConnectionResolver
{

    public function __construct(private ConnectionInterface $connection)
    {
    }

    /**
     * Get the value of connection
     */
    public function getConnection(): ?ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Set the value of connection
     *
     * @return  self
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }
}
