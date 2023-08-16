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
    const STREAM_READ_WRITE_TIMEOUT = 10;
    const STREAM_BUFFER_LENGTH = 8192;

    /**
     * Run task
     * 
     * @param Task|callable $task Task instance or Callable function to run
     * @param bool $wait Wait for response or run asynchronously
     */
    public static function runTask(Task|callable $task, bool $wait = false): mixed
    {
        app()->throwIfNotAsync("Async task execution is only available when app is running in async mode");

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $body = strval($task->getRequest(!$wait));
        return self::withWorker($task->getName(), $body, $wait);
    }

    /**
     * Run tasks
     * 
     * @param Task[]|callable[] $tasks List of Task instance to run
     * @param bool $wait Wait for response or run asynchronously
     * @return Generator
     */
    public static function runTasks(array $tasks, bool $wait = false): Generator
    {
        app()->throwIfNotAsync("Async tasks execution is only available when app is running in async mode");

        $fibers = array_map(function (Task|callable $task) use ($wait) {
            $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
            $body = strval($task->getRequest(!$wait));
            return self::withFiberWorker($task->getName(), $body, $wait);
        }, $tasks);
        foreach ($fibers as $key => $fiber) {
            $fiber->resume();
            yield $key => $fiber->getReturn();
        }
        return null;
    }

    /**
     * Run task using task worker with fibers
     * 
     * @param string $id Task request id
     * @param string $body Task request body
     * @param bool $wait Wait for response or run asynchronously
     * @param int $length Max stream length in bytes
     * @return Fiber
     */
    public static function withFiberWorker(string $id, string $body, bool $wait = false, int $length = self::STREAM_BUFFER_LENGTH): Fiber
    {
        $fiber = new Fiber(function (string $id, string $body, bool $wait, int $length) {
            $socket = self::connect($id, true, false);
            if ($socket == false) {
                return false;
            }

            // Send the data
            if ($wait) {
                stream_write($socket, $body, $length);
            } else {
                self::streamLoop($socket, false, function ($socket, $length, $body) {
                    stream_write($socket, $body, $length);
                }, [$socket, $length, $body]);
            }

            // Suspend fiber after sending requesst
            $callback = Fiber::suspend();

            if ($callback && is_callable($callback)) {
                return self::streamLoop($socket, true, function ($socket, $length, $callback) {
                    // Receive the response.
                    $response = stream_read($socket, $length);
                    // Close socket
                    $socket && fclose($socket) && $socket = null;
                    // Return response
                    call_user_func($callback, $response ? unserialize($response) : null);
                }, [$socket, $length, $callback]);
            } else if (!$wait) {
                return self::streamLoop($socket, true, function ($socket) {
                    // Close socket
                    $socket && fclose($socket) && $socket = null;
                }, [$socket]);
            } else {
                // Receive the response.
                $response = stream_read($socket, $length);
                // Close socket
                $socket && fclose($socket) && $socket = null;
                // Return response. 
                return $response ? unserialize($response) : null;
            }
        });

        // Start running fiber
        $fiber->start($id, $body, $wait, $length);

        return $fiber;
    }

    /**
     * Run task using task worker
     * 
     * @param string $id Task request id
     * @param string $body Task request body
     * @param bool $wait Wait for response or run asynchronously
     * @param int $length Max stream length in bytes
     * @return mixed `false` if failed
     */
    public static function withWorker(string $id, string $body, bool $wait = true, int $length = self::STREAM_BUFFER_LENGTH): mixed
    {
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
            return self::streamLoop($socket, false, function ($socket, $length, $body) {
                // Send the data
                stream_write($socket, $body, $length);
                // Close socket - for non-persistent connection
                $socket && fclose($socket) && $socket = null;
            }, [$socket, $length, $body]);
        }
    }

    /**
     * Process task with event loop
     * 
     * @param Runnable|callable $task Task to run
     * @param ?callable $callback Event Loop Callback
     * @return bool
     */
    public static function taskLoop(Runnable|callable $task, ?callable $callback = null): bool
    {
        if (!app()->async || !isset(app()->worker) || empty(Worker::getEventLoop())) {
            throw new SystemError("Event loop is not available");
        }

        $task = $task instanceof Runnable ? $task : new CallableTask(Closure::fromCallable($task));

        return !!Timer::add(.1, function (Runnable $task, ?callable $callback) {
            try {
                $result =  $task->run();
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
    public static function streamLoop(mixed $fd, bool $readonly, callable $callback, $params = []): bool
    {
        if (!app()->async || !isset(app()->worker) || empty(Worker::getEventLoop())) {
            throw new SystemError("Event loop is not available");
        }

        $flag = $readonly ? Event::EV_READ : Event::EV_WRITE;

        return !!Worker::getEventLoop()->add($fd, $flag, function ($stream) use ($flag, $callback, $params) {
            try {
                Worker::getEventLoop()->del($stream, $flag);
                return call_user_func($callback, ...$params);
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
        if (!app()->async || !isset(app()->taskWorker)) {
            throw new SystemError("Task worker is not available");
        }

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
