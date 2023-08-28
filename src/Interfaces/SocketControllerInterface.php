<?php

namespace Armie\Interfaces;

use Workerman\Connection\ConnectionInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface SocketControllerInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    public function onConnect(ConnectionInterface $connection): void;

    /**
     * @param ConnectionInterface $connection
     * @param mixed               $data
     *
     * @return void
     */
    public function onMessage(ConnectionInterface $connection, mixed $data): void;

    /**
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    public function onClose(ConnectionInterface $connection): void;
}
