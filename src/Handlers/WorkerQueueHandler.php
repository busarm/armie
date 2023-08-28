<?php

namespace Armie\Handlers;

use Armie\App;
use Armie\Async;
use Armie\Errors\QueueError;
use Armie\Interfaces\QueueHandlerInterface;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use LimitIterator;
use SplQueue;
use Workerman\Timer;

/**
 * Handle event operations.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class WorkerQueueHandler implements QueueHandlerInterface
{
    /**
     * Queue store.
     *
     * @var SplQueue<Task>
     */
    public static ?SplQueue $queue;

    /**
     * Queue timer instance.
     */
    public static $queueTimer = null;

    /**
     * Queue is idle - not running any task.
     */
    public static bool $queueIdle = true;

    /**
     * @param App $app
     * @param int $queueRateLimit Queue rate limit: Allowed number of request processed per second per worker. Between 1 to 1000. Must be less than `queueLimit`. **IMPORTANT**: To prevent denial-of-service due to queue spamming
     * @param int $queueLimit     Queue limit: Allowed number of tasks in queue. **IMPORTANT**: To prevent denial-of-service due to queue spamming
     */
    public function __construct(private App $app, private int $queueRateLimit = 100, private int $queueLimit = 10000)
    {
        $this->queueRateLimit = min($this->queueRateLimit ?: 1, min($this->queueLimit, 1000));

        self::$queue = self::$queue ?? new SplQueue();
        self::$queue->setIteratorMode(SplQueue::IT_MODE_FIFO | SplQueue::IT_MODE_DELETE);
    }

    /**
     * @inheritDoc
     */
    public function enqueue(Task $task): void
    {
        $this->app->throwIfNoEventLoop();

        if (count(self::$queue) >= $this->queueLimit) {
            throw new QueueError('Queue limit exceeded. Please try again later');
        }

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));

        // Add to queue
        self::$queue->enqueue($task);

        // Run queue
        self::$queueTimer = self::$queueTimer ?: Timer::add(1, function ($limit) {
            if (self::$queueIdle) {
                try {
                    // --- Start Batch ---- //
                    self::$queueIdle = false;
                    $batch = [];
                    foreach ((new LimitIterator(self::$queue, 0, $limit)) as $task) {
                        $batch[] = $task;
                    }
                    foreach (Async::runTasks($batch) as $key => $result) {
                        if ($result === false) {
                            self::$queue->enqueue($batch[$key]); // Failed queue again
                        }
                    }
                    self::$queueIdle = true;
                    // --- End Batch ---- //

                    // Queue completed
                    if (self::$queue->count() == 0) {
                        Timer::del(self::$queueTimer);
                        self::$queueTimer = null;
                    }
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
