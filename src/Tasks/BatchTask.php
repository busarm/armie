<?php

namespace Armie\Tasks;

use Armie\Dto\TaskDto;
use Armie\Promise;

use function Armie\Helpers\await;
use function Armie\Helpers\report;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class BatchTask extends Task
{
    /**
     * @param iterable<string,string> $tasks List of task request @see Task::getRequest
     */
    public function __construct(protected iterable $tasks)
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     *
     * @return iterable|bool|null
     */
    public function run()
    {
        /** @var array<string,Promise> */
        $batch = [];

        // Create batched promise request
        foreach ($this->tasks as $data) {
            if ($data && ($dto = TaskDto::parse($data))) {
                if ($task = Task::parse($dto)) {
                    try {
                        $batch[$task->getName()] = new Promise($task);
                    } catch (\Throwable $th) {
                        report()->exception($th);
                        $batch[$task->getName()] = new Promise(new \Fiber(fn () => false));
                    }
                }
            }
        }

        // Resove batch result
        return !empty($batch) ? await(Promise::all($batch)) : [];
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        return [
            'tasks' => iterator_to_array($this->tasks),
        ];
    }
}
