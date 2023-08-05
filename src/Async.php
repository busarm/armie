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
use Workerman\Timer;

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
        // Use worker
        if (app()->async && isset(app()->taskWorker)) {
            $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
            $body = strval($task->getRequest(true, app()->config->secret));
            return $wait
                ? self::withWorker($task->getName(), $body, self::STREAM_BUFFER_LENGTH)
                : self::withBufferedWorker($task->getName(), $body, self::STREAM_BUFFER_LENGTH);
        }

        // Use default
        else {
            $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
            return $task->run();
        }
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
        // Use worker
        if (app()->async && !empty(app()->taskWorker)) {
            // Run with fiber worker
            if ($wait) {
                /** @var \Fiber[] */
                $fibers = [];
                foreach ($tasks as $task) {
                    $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                    $body = strval($task->getRequest(true, app()->config->secret));
                    $fibers[] = self::withFiberWorker($task->getName(), $body, self::STREAM_BUFFER_LENGTH);
                }
                foreach ($fibers as $fiber) {
                    $fiber->resume();
                    yield $fiber->getReturn();
                }
            }
            // Run with buffered worker
            else {
                foreach ($tasks as $task) {
                    $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                    $body = strval($task->getRequest(true, app()->config->secret));
                    yield self::withBufferedWorker($task->getName(), $body, self::STREAM_BUFFER_LENGTH);
                }
            }
        }

        // Use default
        else {
            foreach ($tasks as $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                yield $task->run();
            }
        }

        return null;
    }

    /**
     * Run task using task with throttled worker
     * 
     * @param string $id Task request id
     * @param string $body Task request body
     * @param int $length Max stream length in bytes
     * @return null
     */
    private static function withBufferedWorker(string $id, string $body, int $length): mixed
    {
        // Add to buffer
        self::$buffer = self::$buffer ?? new Bag();
        self::$buffer->set($id, $body);

        // Get rate limit
        $limit = (int) ceil(min(self::$bufferRateLimit, self::MAX_RATE_LIMIT) / 10);

        // Start bufferring
        self::$bufferTimer = self::$bufferTimer ?: Timer::add(.1, function ($length, $limit) {
            $tasks = self::$buffer->slice(0, $limit);
            foreach ($tasks as $key => $task) {
                print_r("Buffer started - $key \n");
                if (self::withWorker($key, $task, $length, false) !== false) {
                    print_r("Buffer finished - $key \n");
                    self::$buffer->remove($key);
                }
            }
            if (self::$buffer->count() == 0) {
                Timer::del(self::$bufferTimer);
                print_r("Buffer completed \n");
                self::$bufferTimer = null;
            }
        }, [$length, $limit], true);

        return null;
    }

    /**
     * Run task using task worker with fibers
     * 
     * @param string $id Task request id
     * @param string $body Task request body
     * @param int $length Max stream length in bytes
     * @return Fiber
     */
    private static function withFiberWorker(string $id, string $body, int $length): Fiber
    {
        $fiber = new Fiber(function (string $id, string $body, int $length) {
            $socket = self::connect($id, false, false);

            print_r("Writing request for task $id; socket " . get_resource_id($socket) . PHP_EOL);
            // Send the data
            stream_write($socket, $body, $length);

            // Suspend fiber after sending requesst
            Fiber::suspend();

            print_r("Reading response for task $id; socket " . get_resource_id($socket) . PHP_EOL);
            // Receive the response.
            $response = stream_read($socket, $length);

            print_r("Closing socket " . get_resource_id($socket) . " for task $id\n");
            // Close socket - for non-persistent connection
            $socket && fclose($socket) && $socket = null;

            print_r("Sending result for task $id \n");
            // Return response. 
            return $response ? unserialize($response) : null;
        });

        // Start running fiber
        $fiber->start($id, $body, $length);

        return $fiber;
    }

    /**
     * Run task using task worker
     * 
     * @param string $id Task request id
     * @param string $body Task request body
     * @param int $length Max stream length in bytes
     * @param bool $wait Wait for response or run asynchronously
     * @return mixed `false` if failed
     */
    private static function withWorker(string $id, string $body, int $length, $wait = true): mixed
    {
        static $socket = null;
        $async = !$wait;
        $persist = !$async;

        // Create connection
        if (!$socket) {
            $socket = self::connect($id, $async, $persist);
            if ($socket == false) {
                return false;
            }
        }

        print_r("Writing request for task $id; socket " . get_resource_id($socket) . PHP_EOL);
        // Send the data
        stream_write($socket, $body, $length);

        $wait && print_r("Reading response for task $id; socket " . get_resource_id($socket) . PHP_EOL);
        // Receive the response.
        $response = $wait ? stream_read($socket, $length) : null;

        !$persist && print_r("Closing socket " . get_resource_id($socket) . " for task $id\n");
        // Close socket - for non-persistent connection
        !$persist && $socket && fclose($socket) && $socket = null;

        print_r("Sending result for task $id \n");
        // Return response. 
        return $wait && $response ? unserialize($response) : null;
    }

    /**
     * Connect to task worker and return connection
     * 
     * @param string $id Task request id
     * @param bool $async Async connection
     * @param bool $persist Peristent connection
     * @return resource|false `false` if failed: If $async = true
     * @throws SystemError If $async = false
     */
    private static function connect(string $id, bool $async = false, bool $persist = false): mixed
    {
        $address = app()->taskWorker?->getSocketName();

        $flag = !$async
            ? ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT : STREAM_CLIENT_CONNECT)
            : ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT : STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!($socket = stream_socket_client($address, $errorCode, $errorMsg, self::STREAM_TIMEOUT, $flag))) {
            if (!$async) throw new SystemError(sprintf("Failed to connect to %s for %s: [%s] %s.", $address,  $id, $errorCode, $errorMsg));
            return false;
        }

        // Set blocking mode
        stream_set_blocking($socket, !$async);

        // Set read/write timeout
        stream_set_timeout($socket, self::STREAM_READ_WRITE_TIMEOUT);

        return $socket;
    }
}
