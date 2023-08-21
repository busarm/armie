<?php

namespace Armie;

use Armie\Errors\SystemError;
use Armie\Interfaces\Runnable;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use Fiber;
use Generator;
use Traversable;
use Workerman\Events\Event;
use Workerman\Timer;
use Workerman\Worker;

use function Armie\Helpers\app;
use function Armie\Helpers\log_warning;
use function Armie\Helpers\report;
use function Armie\Helpers\stream_read;
use function Armie\Helpers\stream_write;

/**
 * Handle async operations
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Async
{
    const STREAM_TIMEOUT = 30;
    const STREAM_READ_WRITE_TIMEOUT = 30;
    const STREAM_BUFFER_LENGTH = 8192;
    const MAX_CHILD_PROCESSES = 10;

    /**
     * Run task asynchronously
     * 
     * @param Task|callable $task Task instance or Callable function to run
     */
    public static function runTask(Task|callable $task): mixed
    {
        // Use Task Worker
        if (app()->async && app()->taskWorker) {
            return self::withWorker($task, false);
        }
        // Use event loop
        else if (app()->async && app()->worker) {
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
     * Run tasks asynchronously
     * 
     * @param Task[]|callable[]|Traversable<Task|callable> $tasks List of Task instance to run
     * @param bool $wait Wait for result
     * @return Generator
     */
    public static function runTasks(Traversable|array $tasks, bool $wait = false): Generator
    {
        // Use Task Worker
        if (app()->async && app()->taskWorker) {
            $fibers = array_map(function (Task|callable $task) use ($wait) {
                return self::withFiberWorker($task, $wait);
            }, $tasks);
            foreach ($fibers as $key => $fiber) {
                $fiber->resume();
                yield $key => $fiber->getReturn();
            }
            return null;
        }
        // Use event loop
        else if (app()->async && app()->worker && !$wait) {
            $results = array_map(function (Task|callable $task) {
                return self::withEventLoop($task);
            }, $tasks);
            foreach ($results as $key => $result) {
                yield $key => $result;
            }
        }
        // Use process forking
        else if (!$wait) {
            $results = array_map(function (Task|callable $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                return self::withChildProcess($task) ?: $task->run();
            }, $tasks);
            foreach ($results as $key => $result) {
                yield $key => $result;
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
     * @param Task|callable $task Task to run
     * @param bool $wait Wait for response
     * @param int $length Max stream length in bytes
     * @return Fiber
     */
    public static function withFiberWorker(Task|callable $task, bool $wait = false, int $length = self::STREAM_BUFFER_LENGTH): Fiber
    {
        app()->throwIfNoEventLoop();
        app()->throwIfNoTaskWorker();

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $id = $task->getName();
        $body = strval($task->getRequest(!$wait));

        $fiber = new Fiber(function (string $id, string $body, bool $wait, int $length) {

            $socket = self::connect($id, true, false);
            if ($socket == false) {
                return false;
            }

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
                return $result ? unserialize($result) : null;
            } else {
                // Close socket
                $socket && fclose($socket) && $socket = null;
                return true;
            }
        });

        // Start running fiber
        $fiber->start($id, $body, $wait, $length);

        return $fiber;
    }

    /**
     * Run task using task worker
     * 
     * @param Task|callable $task Task to run
     * @param bool $wait Wait for response
     * @param int $length Max stream length in bytes
     * @return mixed `false` if failed
     */
    public static function withWorker(Task|callable $task, bool $wait = true, int $length = self::STREAM_BUFFER_LENGTH): mixed
    {
        app()->throwIfNoTaskWorker();

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $id = $task->getName();
        $body = strval($task->getRequest(!$wait));

        // Create connection
        $socket = self::connect($id, $wait, $wait);
        if ($socket == false) {
            return false;
        }

        if ($wait) {
            // Send the data
            stream_write($socket, $body, $length);
            // Receive the response.
            $response = stream_read($socket, $length);
            // Return response. 
            return $response ? unserialize($response) : null;
        } else {
            return self::streamEventLoop($socket, false, function ($socket, $length, $body) {
                // Send the data
                stream_write($socket, $body, $length);
                // Close socket - for non-persistent connection
                $socket && fclose($socket) && $socket = null;
            }, [$socket, $length, $body]);
        }
    }

    /**
     * Run task using independent child process
     * 
     * @param Runnable|callable $task Task to run
     * @param ?callable $callback Event Loop Callback
     * @return int|bool PID of child process or `false` if failed
     */
    public static function withChildProcess(Runnable|callable $task, ?callable $callback = null): int|bool
    {
        if (!extension_loaded('pcntl')) {
            log_warning("Async::withChildProcess requires `pcntl` extension");
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
        else if ($pid) {
            $children++;
            $status = null;
            if ($children >= self::MAX_CHILD_PROCESSES) {
                pcntl_waitpid($pid, $status, WNOHANG);
            }
            return $pid;
        }
        // Child process
        else {
            // Set timeout alarm for child
            pcntl_alarm(self::STREAM_READ_WRITE_TIMEOUT);
            // Execute task
            $result =  $task->run();
            $callback && call_user_func($callback, $result);
            // Exit child process if not waited
            $children = max($children - 1, 0);
            die();
        }
    }

    /**
     * Run task with event loop
     * 
     * @param Runnable|callable $task Task to run
     * @param ?callable $callback Event Loop Callback
     * @return int|bool Timer Id of `fale` if failed
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
     * Process stream with event loop
     * 
     * @param resource $fd Stream File Descriptor
     * @param bool $readonly Read only stream. `false` if Read / Write
     * @param callable $callback Event Loop Callback
     * @param array $params Event Loop Params
     * @return bool
     */
    public static function streamEventLoop(mixed $fd, bool $readonly, callable $callback, array $params = []): bool
    {
        app()->throwIfNoEventLoop();

        $flag = $readonly ? Event::EV_READ : Event::EV_WRITE;

        return !!Worker::getEventLoop()->add($fd, $flag, function ($stream) use ($flag, $callback, $params) {
            try {
                Worker::getEventLoop()->del($stream, $flag);
                call_user_func($callback, ...$params);
            } catch (\Throwable $th) {
                report()->exception($th);
            }
        });
    }

    /**
     * Connect to task worker
     * 
     * @param string $id Task request id
     * @param bool $block Blocking or Non-Blocking connection
     * @param bool $persist Peristent connection
     * @return resource|false  Return connection or `false` if failed and $async = true
     * @throws SystemError If failed and $async = false
     */
    private static function connect(string $id, bool $block = false, bool $persist = false): mixed
    {
        app()->throwIfNoEventLoop();
        app()->throwIfNoTaskWorker();

        static $connection = null;

        $address = app()->taskWorker->getSocketName();

        $flag = $block
            ? ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT : STREAM_CLIENT_CONNECT)
            : ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT : STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if ($persist && $connection) {
            $socket = $connection;
        } else if (!($socket = stream_socket_client($address, $errorCode, $errorMsg, self::STREAM_TIMEOUT, $flag))) {
            if ($block) throw new SystemError(sprintf("Failed to connect to %s for %s: [%s] %s.", $address,  $id, $errorCode, $errorMsg));
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
