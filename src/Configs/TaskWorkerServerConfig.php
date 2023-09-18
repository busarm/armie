<?php

namespace Armie\Configs;

use Armie\Enums\Cron;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use DateTimeInterface;

/**
 * Async Task Worker Server Configuration.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class TaskWorkerServerConfig extends ServerConfig
{
    /**
     * Worker host address.
     *
     * - `UNIX`   - e.g `unix:///tmp/task.sock` or `/tmp/task.sock`. Ensure that socket file has appropraite permissions and resides locally not on a network/mounted drive.
     * - `TCP`    - e.g `tcp://localhost:8000` or `localhost:8000`.
     */
    public ?string $workerHost = null;

    /**
     * Number of task worker processes to spawn. Set to 0 to disable task worker.
     */
    public int $taskWorkers = 1;

    /**
     * Max number of task requests to handle per worker before reloading worker. This is to prevent memory leak.
     */
    public int $taskMaxRequests = 100000;

    /**
     * Timer Jobs.
     *
     * @var array<int|string,Task[]>
     */
    public array $jobs = [];


    /**
     * Set worker host address.
     * 
     * - `UNIX`   - e.g `unix:///tmp/task.sock` or `/tmp/task.sock`. Ensure that socket file has appropraite permissions and resides locally not on a network/mounted drive.
     * - `TCP`    - e.g `tcp://localhost:8000` or `localhost:8000`.
     * 
     * @return  static
     */
    public function setWorkerHost($workerHost): static
    {
        $this->workerHost = $workerHost;

        return $this;
    }

    /**
     * Set number of taks workers to spawn. Set to 0 or false to disable. Default: 100,000.
     *
     * @return static
     */
    public function setTaskWorkers(int|false $taskWorkers): static
    {
        $this->taskWorkers = intval($taskWorkers);

        return $this;
    }

    /**
     * Set max number of task requests to handle per worker before reloading worker. This is to prevent memory leak.
     *
     * @return static
     */
    public function setTaskMaxRequests(int $taskMaxRequests)
    {
        $this->taskMaxRequests = $taskMaxRequests;

        return $this;
    }

    /**
     * Add timer Jobs.
     *
     * @param Task|callable              $job  Job to perform
     * @param Cron|DateTimeInterface|int $cron When to perform job.
     *                                         Use `Cron::*` or `integer` value (seconds) for recuring jobs.
     *                                         Use `DateTimeInterface` for one-time job.
     *
     * @return static
     */
    public function addJob(Task|callable $job, Cron|DateTimeInterface|int $cron): static
    {
        $job = $job instanceof Task ? $job : new CallableTask(Closure::fromCallable($job));

        if ($cron instanceof Cron) {
            if (!isset($this->jobs[$cron->value])) {
                $this->jobs[$cron->value] = [];
            }
            $this->jobs[$cron->value][] = $job;
        } elseif ($cron instanceof DateTimeInterface) {
            $date = $cron->format(DATE_W3C);
            if (!isset($this->jobs[$date])) {
                $this->jobs[$date] = [];
            }
            $this->jobs[$date][] = $job;
        } else {
            if (!isset($this->jobs[$cron])) {
                $this->jobs[$cron] = [];
            }
            $this->jobs[$cron][] = $job;
        }

        return $this;
    }
}
