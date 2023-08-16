<?php

namespace Armie\Interfaces;

use Armie\Tasks\Task;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface QueueHandlerInterface
{
    /**
     * Queue task
     * 
     * @param Task $task
     */
    public function enqueue(Task $task): void;

    /**
     * Remove from queue
     * 
     * @param string $id
     */
    public function dequeue(string $id): void;
}
