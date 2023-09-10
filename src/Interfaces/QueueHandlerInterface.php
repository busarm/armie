<?php

namespace Armie\Interfaces;

use Armie\Tasks\Task;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface QueueHandlerInterface
{
    /**
     * Queue task.
     *
     * @param Task                                 $task
     * @param callable|class-string<Runnable>|null $listner
     */
    public function enqueue(Task $task, callable|string|null $listner = null): void;

    /**
     * Remove from queue.
     *
     * @param string|int $id
     */
    public function dequeue(string|int $id): void;
}
