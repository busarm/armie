<?php

namespace Armie\Configs;

use Armie\Enums\Cron;
use Armie\Enums\Looper;
use Armie\Errors\SystemError;
use Armie\Interfaces\SocketControllerInterface;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use DateTimeInterface;

/**
 * Application Worker Configuration
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license s://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ServerConfig
{
    /**
     * Server Url
     */
    public ?string $serverUrl = null;

    /**
     * Number of http worker processes to spawn. Minimum = 1
     */
    public int $httpWorkers = 2;

    /**
     * Number of task worker processes to spawn. Set to 0 to disable task worker.
     */
    public int $taskWorkers = 1;

    /**
     * Event loop type
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


    // ------------- SSL -----------------//

    /**
     * SSL is enabled
     *
     * @var bool
     */
    public bool $sslEnabled = false;

    /**
     * SSL certificate path
     *
     * @var string|null
     */
    public string|null $sslCertPath = null;

    /**
     * SSL primary key path
     *
     * @var string|null
     */
    public string|null $sslPkPath = null;

    /**
     * SSL verify peer
     *
     * @var bool
     */
    public bool $sslVerifyPeer = false;

    /**
     * Timer Jobs
     *
     * @var array<string,Task[]>
     */
    public array $jobs = [];

    /**
     * Socket Connection Handlers
     *
     * @var array<string,class-string<SocketControllerInterface>>
     */
    public array $sockets = [];

    /**
     * Set server Url
     *
     * @return  self
     */
    public function setServerUrl($serverUrl)
    {
        $this->serverUrl = $serverUrl;

        return $this;
    }

    /**
     * Set number of http workers to spawn. Minimum = 1
     *
     * @return  self
     */
    public function setHttpWorkers(int $httpWorkers): self
    {
        $this->httpWorkers = $httpWorkers;

        return $this;
    }

    /**
     * Set number of taks workers to spawn. Set to 0 or false to disable
     *
     * @return  self
     */
    public function setTaskWorkers(int|false $taskWorkers): self
    {
        $this->taskWorkers = intval($taskWorkers);

        return $this;
    }

    /**
     * Set event lool type
     *
     * @param  Looper  $looper  Event lool type
     *
     * @return  self
     */
    public function setLooper(Looper $looper): self
    {
        $this->looper = $looper;

        return $this;
    }

    /**
     * Set full path to worker's pid file
     *
     * @return  self
     */
    public function setPidFilePath(string $pidFilePath): self
    {
        $this->pidFilePath = $pidFilePath;

        return $this;
    }

    /**
     * Set full path to worker's status file
     *
     * @return  self
     */
    public function setStatusFilePath(string $statusFilePath): self
    {
        $this->statusFilePath = $statusFilePath;

        return $this;
    }

    /**
     * Set full path to worker's log file
     *
     * @return  self
     */
    public function setLogFilePath(string $logFilePath): self
    {
        $this->logFilePath = $logFilePath;

        return $this;
    }

    /**
     * Set SSL is enabled
     *
     * @param  bool  $sslEnabled  SSL is enabled
     *
     * @return  self
     */
    public function setSslEnabled(bool $sslEnabled)
    {
        $this->sslEnabled = $sslEnabled;

        return $this;
    }

    /**
     * Set SSL certificate path
     *
     * @param  string|null  $sslCertPath  SSL certificate path
     *
     * @return  self
     */
    public function setSslCertPath($sslCertPath)
    {
        $this->sslCertPath = $sslCertPath;

        return $this;
    }

    /**
     * Set SSL primary key path
     *
     * @param  string|null  $sslPkPath  SSL primary key path
     *
     * @return  self
     */
    public function setSslPkPath($sslPkPath)
    {
        $this->sslPkPath = $sslPkPath;

        return $this;
    }

    /**
     * Set SSL verify peer
     *
     * @param  bool  $sslVerifyPeer  SSL verify peer
     *
     * @return  self
     */
    public function setSslVerifyPeer(bool $sslVerifyPeer)
    {
        $this->sslVerifyPeer = $sslVerifyPeer;

        return $this;
    }

    /**
     * Add timer Jobs
     *
     * @param Task|callable $job Job to perform
     * @param Cron|DateTimeInterface|int $cron When to perform job. 
     * Use `Cron::*` or `integer` value (seconds) for recuring jobs. 
     * Use `DateTimeInterface` for one-time job. 
     * @return  self
     */
    public function addJob(Task|callable $job, Cron|DateTimeInterface|int $cron): self
    {
        $job = $job instanceof Task ? $job : new CallableTask(Closure::fromCallable($job));

        if ($cron instanceof Cron) {
            if (!isset($this->jobs[$cron->value])) {
                $this->jobs[$cron->value] = [];
            }
            $this->jobs[$cron->value][] = $job;
        } else if ($cron instanceof DateTimeInterface) {
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

    /**
     * Add socket connection
     *
     * @param integer $port Socket port
     * @param class-string<SocketControllerInterface> $controller Socket controller class
     * @return self
     */
    public function addSocket(int $port, string $controller): self
    {
        if (!is_subclass_of($controller, SocketControllerInterface::class)) {
            throw new SystemError("`$controller` does not implement " . SocketControllerInterface::class);
        }

        $port = (string)$port;
        $this->sockets[$port] = $controller;
        return $this;
    }
}
