<?php

namespace Armie\Handlers;

use Armie\Bags\Bag;
use Armie\Errors\QueueError;
use Armie\Interfaces\QueueHandlerInterface;
use Armie\Interfaces\StorageBagInterface;
use Armie\Tasks\BatchTask;
use Armie\Tasks\Task;
use LimitIterator;
use Workerman\Timer;

use function Armie\Helpers\app;
use function Armie\Helpers\dispatch;
use function Armie\Helpers\listen;
use function Armie\Helpers\report;

/**
 * Handle queue operations. Only available if app is running in async mode.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class WorkerQueueHandler implements QueueHandlerInterface
{
    /**
     * Queue timer instance.
     */
    public static $queueTimer = null;

    /**
     * Queue is idle - not running any task.
     */
    public static bool $queueIdle = true;

    /**
     * Queue try count
     */
    public static array $queueCounts = [];

    /**
     * @param int $rate                             Queue rate limit: Allowed number of request processed per second per worker. Between 1 to 100. Must be less than `limit`. **IMPORTANT**: To prevent denial-of-service due to queue spamming
     * @param int $limit                            Queue limit: Max number of tasks allowed in queue. **IMPORTANT**: To prevent denial-of-service due to queue spamming
     * @param int $maxRetry                         Queue max retry count: Max number of times to retry task if failed. Default: 5
     * @param ?StorageBagInterface<string> $store   Queue store: Used to persist queue. Default @see Bag
     */
    public function __construct(private int $rate = 10, private int $limit = 10000, private int $maxRetry = 5, private ?StorageBagInterface $store = null)
    {
        $this->rate = min($this->rate ?: 1, min($this->limit, 100));

        $this->store = $this->store ?? new Bag();
    }

    /**
     * Run queue
     */
    public function run(): void
    {
        if (self::$queueTimer || $this->store->count() == 0) return;

        self::$queueTimer = Timer::add(1, function () {

            // Queue busy
            if (!self::$queueIdle) return;

            // Queue completed
            if ($this->store->count() == 0) {
                Timer::del(self::$queueTimer);
                self::$queueTimer = null;
                return;
            }

            try {
                // --- Start Batch ---- //

                self::$queueIdle = false;

                $results = (new BatchTask(new LimitIterator($this->store->itterate(), 0, $this->rate)))->run();
                if ($results && is_iterable($results)) {
                    foreach ($results as $key => $result) {
                        $count = (self::$queueCounts[$key] ?? 0);
                        // Success
                        if ($result !== false) {
                            // Dispatch event
                            dispatch($key, ['result' => $result]);
                            unset(self::$queueCounts[$key]);
                            $this->store->remove($key);
                        }
                        // Max retries
                        else if ($count >= $this->maxRetry) {
                            unset(self::$queueCounts[$key]);
                            $this->store->remove($key);
                        }
                        // Increase queue counter
                        else {
                            self::$queueCounts[$key] = $count + 1;
                        }
                    }
                }

                self::$queueIdle = true;
                // --- End Batch ---- //

            } catch (\Throwable $th) {
                report()->exception($th);
            }
        }, [], true);
    }

    /**
     * @inheritDoc
     * 
     * Note: If task returns `false` queue will fail and retry
     */
    public function enqueue(Task $task, callable|string|null $listner = null): void
    {
        app()->throwIfNoEventLoop();

        if ($this->store->count() >= $this->limit) {
            throw new QueueError('Queue limit exceeded. Please try again later');
        }

        // Listen to task event
        $listner && listen($task->getName(), $listner);

        // Add to queue
        $this->store->set($task->getName(), strval($task->getRequest(false))) or throw new QueueError('Failed to queue ' . $task->getName());

        // Run queue
        $this->run();
    }

    /**
     * @inheritDoc
     */
    public function dequeue(string|int $id): void
    {
        $this->store->remove($id);
    }
}
