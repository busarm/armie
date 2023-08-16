<?php

namespace Busarm\PhpMini\Handlers;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Async;
use Busarm\PhpMini\Errors\QueueError;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\QueueHandlerInterface;
use Busarm\PhpMini\Tasks\CallableTask;
use Busarm\PhpMini\Tasks\Task;
use Closure;
use Workerman\Timer;

/**
 * Handle event operations
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class QueueHandler implements QueueHandlerInterface
{
    /**
     * Queue store
     * @var array<Task>
     */
    public static array $queue = [];

    /**
     * Queue timer instance
     */
    public static $queueTimer = null;

    /**
     * Queue is idle - not running any task
     */
    public static bool $queueIdle = true;


    /**
     * @param App $app
     * @param integer $queueRateLimit Queue rate limit: Allowed number of request processed per second. Between 0 to @see self::MAX_RATE_LIMIT. **IMPORTANT**: To prevent denial-of-service due to queue spamming
     * @param integer $queueLimit Queue limit: Allowed number of tasks in queue. **IMPORTANT**: To prevent denial-of-service due to queue spamming
     */
    public function __construct(private App $app, private int $queueRateLimit = 300, private int $queueLimit = 10000)
    {
    }

    /**
     * @inheritDoc
     */
    public function enqueue(Task $task): void
    {
        $this->app->throwIfNotAsync("Queueing task is only available when app is running in async mode");

        if (!isset($this->app->taskWorker)) {
            throw new SystemError("Task worker is required for queueing");
        }

        if (count(self::$queue) >= $this->queueLimit) {
            throw new QueueError("Queue limit exceeded. Please try again later");
        }

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));

        // Add to queue
        self::$queue[] = $task;

        // Run queue
        self::$queueTimer = self::$queueTimer ?: Timer::add(1, function ($limit) {
            if (self::$queueIdle) {
                self::$queueIdle = false;
                $results = Async::runTasks(array_slice(self::$queue, 0, $limit, true), false);
                foreach ($results as $id => $data) {
                    if ($data !== false) {
                        unset(self::$queue[$id]);
                    }
                }
                if (count(self::$queue) == 0) {
                    Timer::del(self::$queueTimer);
                    self::$queueTimer = null;
                }
                self::$queueIdle = true;
            }
        }, [$this->queueRateLimit], true);
    }

    /**
     * @inheritDoc
     */
    public function dequeue(string $id): void
    {
        unset(self::$queue[$id]);
    }
}
