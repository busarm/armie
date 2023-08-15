<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Tasks\Task;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
