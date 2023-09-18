<?php

namespace Armie;

use Armie\Interfaces\Runnable;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use Fiber;
use Workerman\Events\Event;
use Workerman\Timer;
use Workerman\Worker;

use function Armie\Helpers\app;
use function Armie\Helpers\log_warning;
use function Armie\Helpers\report;
use function Armie\Helpers\stream_connect;
use function Armie\Helpers\stream_read;
use function Armie\Helpers\stream_write;
use function Armie\Helpers\unserialize;

/**
 * Handle async operations.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Async
{
    const STREAM_TIMEOUT = 30; // Seconds
    const STREAM_BUFFER_LENGTH = 8192; // Bytes
    const MAX_CHILD_PROCESSES = 10;

    /**
     * Run task asynchronously.
     *
     * @param Task|callable $task Task instance or Callable function to run
     */
    public static function runTask(Task|callable $task): mixed
    {
        // Use Task Worker
        if (app()->getTaskWorkerAddress()) {
            return self::withWorker($task, false);
        }
        // Use event loop
        elseif (app()->async) {
            return self::withEventLoop($task);
        }
        // Use default
        else {
            // Try process forking
            $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));

            return self::withChildProcess($task) ?: $task->run();
        }
    }

    /**
     * Run tasks asynchronously. If failed, run synchronously.
     *
     * @param iterable<Task|callable> $tasks List of Task instance to run
     * @param bool                    $wait  Wait for result
     *
     * @return array
     */
    public static function runTasks(iterable $tasks, bool $wait = false): array
    {
        $results = [];

        // Use Task Worker
        if (app()->getTaskWorkerAddress()) {
            if ($wait) {
                /** @var Fiber[] */
                $fibers = [];
                foreach ($tasks as $key => $task) {
                    $fibers[$key] = self::withFiberWorker($task);
                }
                foreach ($fibers as $key => $fiber) {
                    $fiber->resume();
                    $results[$key] = $fiber->getReturn();
                }
            } else {
                foreach ($tasks as $key => $task) {
                    $results[$key] = self::withWorker($task, false);
                }
            }
        }
        // Use event loop
        elseif (app()->async && !$wait) {
            foreach ($tasks as $key => $task) {
                $results[$key] = self::withEventLoop($task);
            }
        }
        // Use process forking
        elseif (!$wait) {
            foreach ($tasks as $key => $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                $results[$key] = self::withChildProcess($task) ?: $task->run();
            }
        }
        // Use default
        else {
            foreach ($tasks as $key => $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                $results[$key] = $task->run();
            }
        }

        return $results;
    }

    /**
     * Run task using task worker with fibers.
     *
     * @param Task|callable $task Task to run
     *
     * @return Fiber
     */
    public static function withFiberWorker(Task|callable $task): Fiber
    {
        app()->throwIfNoTaskWorker();

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $body = strval($task->getRequest(false));

        $fiber = new Fiber(function (string $body, int $length) {
            try {

                $socket = stream_connect(app()->getTaskWorkerAddress(), true, false, self::STREAM_TIMEOUT);
                if (!$socket) return false;

                // Send the data
                stream_write($socket, $body, $length);

                // Suspend fiber after sending requesst
                Fiber::suspend();

                // Receive the result
                $result = stream_read($socket, $length);
                // Close socket
                fclose($socket) && $socket = null;
                // Parse response
                return !empty($result) ? unserialize($result) : true;
            } catch (\Throwable $th) {
                report()->exception($th);

                return false;
            }
        });

        // Start running fiber
        $fiber->start($body, self::STREAM_BUFFER_LENGTH);

        return $fiber;
    }

    /**
     * Run task using task worker.
     *
     * @param Task|callable $task Task to run
     * @param bool          $wait Wait for response
     *
     * @return mixed `false` if failed or `true` if success with no response
     */
    public static function withWorker(Task|callable $task, bool $wait = true): mixed
    {
        app()->throwIfNoTaskWorker();

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $body = strval($task->getRequest(!$wait));

        try {

            $socket = stream_connect(app()->getTaskWorkerAddress(), $wait, $wait, self::STREAM_TIMEOUT);
            if ($socket) {
                if ($wait) {
                    // Send the data
                    stream_write($socket, $body, self::STREAM_BUFFER_LENGTH);
                    // Receive the response.
                    $result = stream_read($socket, self::STREAM_BUFFER_LENGTH);
                    // Return response.
                    return !empty($result) ? unserialize($result) : true;
                } else if (!app()->async) {
                    // Send the data
                    stream_write($socket, $body, self::STREAM_BUFFER_LENGTH);
                    // Close socket - for non-persistent connection
                    fflush($socket) && fclose($socket) && $socket = null;
                    return true;
                } else {
                    return  self::streamEventLoop($socket, false, function ($socket, $body, $length) {
                        // Send the data
                        stream_write($socket, $body, $length);
                        // Close socket - for non-persistent connection
                        $socket && fflush($socket) && fclose($socket) && $socket = null;
                    }, [$socket, $body, self::STREAM_BUFFER_LENGTH]);
                }
            }

            return false;
        } catch (\Throwable $th) {
            report()->exception($th);

            return false;
        }
    }

    /**
     * Run task using independent child process.
     *
     * @param Runnable|callable $task     Task to run
     * @param ?callable         $callback Event Loop Callback
     *
     * @return int|bool PID of child process or `false` if failed
     */
    public static function withChildProcess(Runnable|callable $task, ?callable $callback = null): int|bool
    {
        if (!extension_loaded('pcntl')) {
            log_warning('Please install `pcntl` extension. ' . __FILE__ . ':' . __LINE__);

            return false;
        }
        if (!extension_loaded('posix')) {
            log_warning('Please install `posix` extension. ' . __FILE__ . ':' . __LINE__);

            return false;
        }

        $task = $task instanceof Runnable ? $task : new CallableTask(Closure::fromCallable($task));

        static $children = 0;

        // Fork process
        $pid = pcntl_fork();

        // Fork failed
        if ($pid == -1) {
            return false;
        }
        // Fork success
        elseif ($pid) {
            $children++;
            $status = null;
            if ($children >= self::MAX_CHILD_PROCESSES) {
                pcntl_waitpid($pid, $status, WNOHANG);
            }

            return $pid;
        }
        // Child process
        else {
            // Make child process the session leader
            $sid = posix_setsid();
            if ($sid < 0) {
                exit;
            }
            // Set timeout alarm for child
            pcntl_alarm(self::STREAM_TIMEOUT);
            // Execute task
            $result = $task->run();
            $callback && call_user_func($callback, $result);
            // Exit child process if not waited
            $children = max($children - 1, 0);
            exit;
        }
    }

    /**
     * Run task with event loop.
     *
     * @param Runnable|callable $task     Task to run
     * @param ?callable         $callback Event Loop Callback
     *
     * @return int|bool Timer Id of `false` if failed
     */
    public static function withEventLoop(Runnable|callable $task, ?callable $callback = null): int|bool
    {
        app()->throwIfNoEventLoop();

        $task = $task instanceof Runnable ? $task : new CallableTask(Closure::fromCallable($task));

        return Timer::add(.1, function (Runnable $task, ?callable $callback) {
            try {
                $result = $task->run();
                $callback && call_user_func($callback, $result);
            } catch (\Throwable $th) {
                report()->exception($th);
            }
        }, [$task, $callback], false);
    }

    /**
     * Process stream with event loop.
     *
     * @param resource $fd       Stream File Descriptor
     * @param bool     $readonly Read only stream. `false` if Read / Write
     * @param callable $callback Event Loop Callback
     * @param array    $params   Event Loop Callback Params
     *
     * @return bool
     */
    public static function streamEventLoop(mixed $fd, bool $readonly, callable $callback, array $params = []): bool
    {
        app()->throwIfNoEventLoop();

        $flag = $readonly ? Event::EV_READ : Event::EV_WRITE;

        return (bool) Worker::getEventLoop()->add($fd, $flag, function ($stream) use ($flag, $callback, $params) {
            try {
                Worker::getEventLoop()->del($stream, $flag);
                call_user_func($callback, ...$params);
            } catch (\Throwable $th) {
                report()->exception($th);
            }
        });
    }
}
