<?php

namespace Armie;

use Armie\Errors\SystemError;
use Armie\Interfaces\Runnable;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use Fiber;
use Generator;
use Workerman\Events\Event;
use Workerman\Timer;
use Workerman\Worker;

use function Armie\Helpers\app;
use function Armie\Helpers\log_warning;
use function Armie\Helpers\report;
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
    const STREAM_READ_WRITE_TIMEOUT = 30; // Seconds
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
        if (app()->async && app()->getTaskWorkerAddress()) {
            return self::withWorker($task, false);
        }
        // Use event loop
        elseif (app()->async && app()->getHttpWorkerAddress()) {
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
     * Run tasks asynchronously. If failed, run synchronously
     *
     * @param iterable<Task|callable> $tasks List of Task instance to run
     * @param bool                    $wait  Wait for result
     *
     * @return Generator
     */
    public static function runTasks(iterable $tasks, bool $wait = false): Generator
    {
        // Use Task Worker
        if (app()->async && app()->getTaskWorkerAddress()) {
            /** @var Fiber[] */
            $fibers = [];
            foreach ($tasks as $key => $task) {
                $fibers[$key] = self::withFiberWorker($task, $wait);
            }
            foreach ($fibers as $key => $fiber) {
                $fiber->resume();
                yield $key => $fiber->getReturn();
            }

            return null;
        }
        // Use event loop
        elseif (app()->async && app()->getHttpWorkerAddress() && !$wait) {
            foreach ($tasks as $key => $task) {
                yield $key => self::withEventLoop($task);
            }
        }
        // Use process forking
        elseif (!$wait) {
            foreach ($tasks as $key => $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                yield $key => self::withChildProcess($task) ?: $task->run();
            }
        }
        // Use default
        else {
            foreach ($tasks as $key => $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                yield $key => $task->run();
            }
        }

        return null;
    }

    /**
     * Run task using task worker with fibers.
     *
     * @param Task|callable $task   Task to run
     * @param bool          $wait   Wait for response
     *
     * @return Fiber
     */
    public static function withFiberWorker(Task|callable $task, bool $wait = false): Fiber
    {
        app()->throwIfNoTaskWorker();

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $id = $task->getName();
        $body = strval($task->getRequest(!$wait));

        $fiber = new Fiber(function (string $id, string $body, int $length, bool $wait) {
            try {
                $socket = self::connect($id, true, false);

                // Send the data
                stream_write($socket, $body, $length);

                // Suspend fiber after sending requesst
                Fiber::suspend();

                if ($wait) {
                    // Receive the result
                    $result = stream_read($socket, $length);
                    // Close socket
                    $socket && fclose($socket) && $socket = null;
                    // Parse response
                    return !empty($result) ? unserialize($result) : true;
                } else {
                    // Close socket
                    $socket && fflush($socket) && fclose($socket) && $socket = null;

                    return true;
                }
            } catch (\Throwable $th) {
                report()->exception($th);

                return false;
            }
        });

        // Start running fiber
        $fiber->start($id, $body, self::STREAM_BUFFER_LENGTH, $wait);

        return $fiber;
    }

    /**
     * Run task using task worker.
     *
     * @param Task|callable $task   Task to run
     * @param bool          $wait   Wait for response
     *
     * @return mixed `false` if failed or `true` if success with no response
     */
    public static function withWorker(Task|callable $task, bool $wait = true): mixed
    {
        app()->throwIfNoTaskWorker();

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $id = $task->getName();
        $body = strval($task->getRequest(!$wait));

        try {
            $socket = self::connect($id, $wait, $wait);
            if ($socket) {
                if ($wait) {
                    // Send the data
                    stream_write($socket, $body, self::STREAM_BUFFER_LENGTH);
                    // Receive the response.
                    $result = stream_read($socket, self::STREAM_BUFFER_LENGTH);
                    // Return response.
                    return !empty($result) ? unserialize($result) : true;
                } else {
                    return self::streamEventLoop($socket, false, function ($socket, $body, $length) {
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
            pcntl_alarm(self::STREAM_READ_WRITE_TIMEOUT);
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

    /**
     * Connect to task worker.
     *
     * @param string $id      Task request id
     * @param bool   $block   Blocking or Non-Blocking connection
     * @param bool   $persist Peristent connection
     *
     * @throws SystemError If failed and $block = true
     *
     * @return resource|false Return connection or `false` if failed and $async = true
     */
    private static function connect(string $id, bool $block = false, bool $persist = false): mixed
    {
        app()->throwIfNoTaskWorker();

        static $connection = null;

        $address = app()->getTaskWorkerAddress();

        $flag = $block
            ? ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT : STREAM_CLIENT_CONNECT)
            : ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT : STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if ($persist && $connection) {
            $socket = $connection;
        } elseif (!($socket = stream_socket_client($address, $errorCode, $errorMsg, self::STREAM_TIMEOUT, $flag))) {
            if ($block) {
                throw new SystemError(sprintf('Failed to connect to %s for %s: [%s] %s.', $address, $id, $errorCode, $errorMsg));
            }

            return false;
        }

        // Set blocking mode
        stream_set_blocking($socket, $block);

        // Set read/write timeout
        stream_set_timeout($socket, self::STREAM_READ_WRITE_TIMEOUT);

        if ($persist) {
            $connection = $socket;
        }

        return $socket;
    }
}
