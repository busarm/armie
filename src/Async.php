<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Bags\Bag;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Busarm\PhpMini\Tasks\CallableTask;
use Busarm\PhpMini\Tasks\Task;
use Closure;
use Fiber;
use Generator;
use Workerman\Events\Event;
use Workerman\Timer;
use Workerman\Worker;

use function Busarm\PhpMini\Helpers\app;
use function Busarm\PhpMini\Helpers\stream_read;
use function Busarm\PhpMini\Helpers\stream_write;

/**
 * Handle async operations
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Async
{
    const STREAM_TIMEOUT = 30;
    const STREAM_READ_WRITE_TIMEOUT = 10;
    const STREAM_BUFFER_LENGTH = 8192;
    const MAX_RATE_LIMIT = 1000;

    /**
     * Buffer store
     * @var StorageBagInterface<string>
     */
    public static StorageBagInterface|null $buffer = null;
    /**
     * Buffer rate limit: Number requests per second
     */
    public static $bufferRateLimit = 200;

    /**
     * Buffer timer instance
     */
    private static $bufferTimer = null;

    /**
     * Run task
     * 
     * @param Task|callable $task Task instance or Callable function to run
     * @param bool $wait Wait for response or run asynchronously
     */
    public static function runTask(Task|callable $task, bool $wait = false): mixed
    {
        app()->throwIfNotAsync();

        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
        $body = strval($task->getRequest(!$wait, app()->config->secret));
        return $wait
            ? self::withWorker($task->getName(), $body)
            : self::withBufferedWorker($task->getName(), $body);
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
        app()->throwIfNotAsync();

        // Run with fiber worker
        if ($wait) {
            /** @var \Fiber[] */
            $fibers = [];
            foreach ($tasks as $key => $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                $body = strval($task->getRequest(!$wait, app()->config->secret));
                $fibers[$key] = self::withFiberWorker($task->getName(), $body, true);
            }
            ksort($fibers);
            foreach ($fibers as $key => $fiber) {
                $fiber->resume();
                yield $key => $fiber->getReturn();
            }
        }
        // Run with buffered worker
        else {
            foreach ($tasks as $key => $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                $body = strval($task->getRequest(!$wait, app()->config->secret));
                yield $key => self::withBufferedWorker($task->getName(), $body);
            }
        }
    }

    /**
     * Run task using task with buffered worker
     * 
     * @param string $id Task request id
     * @param string $body Task request body
     * @param int $length Max stream length in bytes
     * @return bool
     */
    public static function withBufferedWorker(string $id, string $body, int $length = self::STREAM_BUFFER_LENGTH): mixed
    {
        if (!app()->async || !isset(app()->taskWorker)) {
            throw new SystemError("Task worker is not available");
        }

        // Add to buffer
        self::$buffer = self::$buffer ?? new Bag();
        self::$buffer->set($id, $body);

        // Get rate limit
        $limit = (int) ceil(min(self::$bufferRateLimit, self::MAX_RATE_LIMIT) / 100) ?: 1;

        // Start bufferring
        self::$bufferTimer = self::$bufferTimer ?: Timer::add(1 / 100, function ($length, $limit) {
            $tasks = self::$buffer->slice(0, $limit);
            foreach ($tasks as $key => $task) {
                if (self::withWorker($key, $task, false, $length) !== false) {
                    self::$buffer->remove($key);
                }
            }
            if (self::$buffer->count() == 0) {
                Timer::del(self::$bufferTimer);
                self::$bufferTimer = null;
            }
        }, [$length, $limit], true);

        return !!self::$bufferTimer;
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
        if (!app()->async || !isset(app()->taskWorker)) {
            throw new SystemError("Task worker is not available");
        }

        $fiber = new Fiber(function (string $id, string $body, bool $wait, int $length) {
            $socket = self::connect($id, true, false);

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

            if (!is_null($callback) || !$wait) {
                return self::streamLoop($socket, true, function ($socket, $length, $callback) {
                    // Receive the response.
                    $response = stream_read($socket, $length);
                    // Close socket
                    $socket && fclose($socket) && $socket = null;
                    // Return response
                    if (is_callable($callback)) {
                        return call_user_func($callback, $response ? unserialize($response) : null);
                    }
                }, [$socket, $length, $callback]);
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
     * Process stream with event loop
     * 
     * @param resource $stream Stream Resource
     * @param bool $readonly Read only stream. `false` if Read / Write
     * @param callable $callback Event Loop Callback
     * @param array $params Event Loop Params
     * @return bool
     */
    public static function streamLoop(mixed $stream, bool $readonly, callable $callback, $params = []): mixed
    {
        if (!app()->async || !isset(app()->worker) || empty(Worker::getEventLoop())) {
            throw new SystemError("Event loop is not available");
        }

        $flag = $readonly ? Event::EV_READ :  Event::EV_WRITE;

        return !!Worker::getEventLoop()->add($stream, $flag, function ($stream) use ($flag, $callback, $params) {
            Worker::getEventLoop()->del($stream, $flag);
            return call_user_func($callback, ...$params);
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

        $flag = !$block
            ? ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT : STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT)
            : ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT : STREAM_CLIENT_CONNECT);


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
