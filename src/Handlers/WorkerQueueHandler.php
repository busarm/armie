<?php

namespace Armie\Handlers;

use Armie\App;
use Armie\Async;
use Armie\Errors\QueueError;
use Armie\Interfaces\QueueHandlerInterface;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use SplQueue;
use Workerman\Timer;

/**
 * Handle event operations
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class WorkerQueueHandler implements QueueHandlerInterface
{
    /**
     * Queue store
     * @var SplQueue<Task>
     */
    public static ?SplQueue $queue;

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
        self::$queue = self::$queue ?? new SplQueue();
        self::$queue->setIteratorMode(SplQueue::IT_MODE_FIFO);
    }

    /**
     * @inheritDoc
     */
    public function enqueue(Task $task): void
    {
        $this->app->throwIfNoEventLoop();
        $this->app->throwIfNoTaskWorker();

        if (count(self::$queue) >= $this->queueLimit) {
            throw new QueueError("Queue limit exceeded. Please try again later");
        }

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));

        // Add to queue
        self::$queue->enqueue($task);

        // Run queue
        self::$queueTimer = self::$queueTimer ?: Timer::add(1, function ($limit) {

            if (self::$queueIdle) {
                try {
                    self::$queueIdle = false;

                    $count = 0;
                    foreach (self::$queue as $task) {
                        if (Async::runTask($task)) {
                            unset(self::$queue[self::$queue->key()]);
                            if (++$count >= $limit) break;
                        }
                    }

                    if (self::$queue->count() == 0) {
                        Timer::del(self::$queueTimer);
                        self::$queueTimer = null;
                    }

                    self::$queueIdle = true;
                } catch (\Throwable $th) {
                    $this->app->reporter->exception($th);
                }
            }
        }, [$this->queueRateLimit], true);
    }

    /**
     * @inheritDoc
     */
    public function dequeue(string|int $id = null): void
    {
        self::$queue->offsetUnset($id ?? self::$queue->key());
    }
}
