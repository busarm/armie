<?php

namespace Armie\Tests\App\V1\Controllers;

use Armie\Interfaces\SocketControllerInterface;
use Workerman\Connection\ConnectionInterface;
use Workerman\Timer;

use function Armie\Helpers\log_debug;
use function Armie\Helpers\log_warning;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class MessengerSocketController implements SocketControllerInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    public function onConnect(ConnectionInterface $connection): void
    {
        log_warning('User connected '.$connection->getRemoteAddress());
    }

    /**
     * @param ConnectionInterface $connection
     * @param mixed               $data
     *
     * @return void
     */
    public function onMessage(ConnectionInterface $connection, mixed $data): void
    {
        log_debug('Message received from '.$connection->getRemoteAddress(), $data);
        Timer::add(1, function () use ($connection) {
            $connection->send('Pong '.time());
        }, [], false);
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    public function onClose(ConnectionInterface $connection): void
    {
        log_warning('User disconnected '.$connection->getRemoteAddress());
    }
}
