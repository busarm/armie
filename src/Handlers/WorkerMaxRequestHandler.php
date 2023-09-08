<?php

namespace Armie\Handlers;

use Workerman\Connection\TcpConnection;

/**
 * Reload worker when max request reached
 * 
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class WorkerMaxRequestHandler
{
    /**
     * @var array<int,int>
     */
    static $requests = [];

    public function __construct(protected int $maxRequests)
    {
    }

    public static function handle(TcpConnection $connection, int $maxRequests)
    {
        $requests = self::$requests[$connection->worker->id] ?? 0;

        // Retart worker if max request reached to prevent memory leak
        if ($requests >= $maxRequests) {
            $requests = 1;
            // Send SIGUSR2 signal to gracefully stop worker
            // Graceful stop ensures that pending/requests won't be lost
            $connection->worker->signalHandler(\SIGUSR2);
        } else {
            $requests += 1;
        }

        self::$requests[$connection->worker->id] = $requests;
    }
}
