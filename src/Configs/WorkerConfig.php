<?php

namespace Busarm\PhpMini\Configs;

use Busarm\PhpMini\Enums\Looper;

/**
 * Application Configuration
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license s://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class WorkerConfig
{
    /**
     * Number of http workers to spawn
     */
    public int $httpWorkers = 4;

    /**
     * Number of taks workers to spawn
     */
    public int $taskWorkers = 2;

    /**
     * Enable task worker
     */
    public bool $useTaskWorker = true;

    /**
     * Event lool type
     * @var Looper
     */
    public Looper $looper = Looper::DEFAULT;

    /**
     * Full path to worker's pid file
     */
    public string|null $pidFilePath = null;

    /**
     * Full path to worker's status file
     */
    public string|null $statusFilePath = null;

    /**
     * Full path to worker's log file
     */
    public string|null $logFilePath = null;

    /**
     * Set number of http workers to spawn
     *
     * @return  self
     */
    public function setHttpWorkers(int $httpWorkers)
    {
        $this->httpWorkers = $httpWorkers;

        return $this;
    }

    /**
     * Set number of taks workers to spawn
     *
     * @return  self
     */
    public function setTaskWorkers(int $taskWorkers)
    {
        $this->taskWorkers = $taskWorkers;

        return $this;
    }

    /**
     * Set enable task worker
     *
     * @return  self
     */
    public function setUseTaskWorker($useTaskWorker)
    {
        $this->useTaskWorker = $useTaskWorker;

        return $this;
    }

    /**
     * Set event lool type
     *
     * @param  Looper  $looper  Event lool type
     *
     * @return  self
     */
    public function setLooper(Looper $looper)
    {
        $this->looper = $looper;

        return $this;
    }

    /**
     * Set full path to worker's pid file
     *
     * @return  self
     */
    public function setPidFilePath(string $pidFilePath)
    {
        $this->pidFilePath = $pidFilePath;

        return $this;
    }

    /**
     * Set full path to worker's status file
     *
     * @return  self
     */
    public function setStatusFilePath(string $statusFilePath)
    {
        $this->statusFilePath = $statusFilePath;

        return $this;
    }

    /**
     * Set full path to worker's log file
     *
     * @return  self
     */
    public function setLogFilePath(string $logFilePath)
    {
        $this->logFilePath = $logFilePath;

        return $this;
    }
}
