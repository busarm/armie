<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Bags\Bag;
use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Busarm\PhpMini\Tasks\CallableTask;
use Busarm\PhpMini\Tasks\Task;
use Closure;
use Generator;
use Workerman\Timer;

use function Busarm\PhpMini\Helpers\app;
use function Busarm\PhpMini\Helpers\get_max_upload_size;
use function Busarm\PhpMini\Helpers\log_error;
use function Opis\Closure\unserialize;

/**
 * Handle async operations
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * 
 * // TODO Add Coroutine support
 */
class Async
{
    const MAX_RATE_LIMIT = 1000;

    /**
     * Buffer store
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
     * @param bool $wait Run asynchronously or wait for response
     */
    public static function runTask(Task|callable $task, $wait = false): mixed
    {
        // Use worker
        if (app()->async && isset(app()->taskWorker)) {
            $length = get_max_upload_size(8196);
            $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
            return $wait ? self::withWorker($task, $length) : self::withThrottledWorker($task, $length);
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
     * @param bool $wait Run asynchronously or wait for response
     * @return Generator
     */
    public static function runTasks(array $tasks, $wait = false): Generator
    {

        // Use worker
        if (app()->async && !empty(app()->taskWorker)) {
            $length = get_max_upload_size(8196);
            foreach ($tasks as $task) {
                $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
                yield $wait ? self::withWorker($task, $length) : self::withThrottledWorker($task, $length);
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
     * Run task using task limitd worker
     * 
     * @param Task $task Task instance to run
     * @param int $length Max stream length
     * @return null
     */
    public static function withThrottledWorker(Task $task, int $length): mixed
    {
        // Add to buffer
        self::$buffer = self::$buffer ?? new Bag();
        self::$buffer->set($task->getName(), $task);

        // Get rate limit
        $limit = (int) ceil(min(self::$bufferRateLimit, self::MAX_RATE_LIMIT) / 10);

        // Start bufferring
        self::$bufferTimer = self::$bufferTimer ?: Timer::add(.1, function ($length, $limit) {
            $splice = array_slice(self::$buffer->all(), 0, $limit);
            foreach ($splice as $key => $task) {
                if (self::withWorker($task, $length, false) !== false) {
                    self::$buffer->remove($key);
                }
            }
            if (self::$buffer->count() == 0) {
                Timer::del(self::$bufferTimer);
                self::$bufferTimer = null;
            }
        }, [$length, $limit], true);

        return null;
    }

    /**
     * Run task using task worker
     * 
     * @param Task $task Task instance to run
     * @param int $length Max stream length
     * @param bool $wait Run asynchronously or wait for response
     * @return mixed `false` if failed
     */
    public static function withWorker(Task $task, int $length, $wait = true): mixed
    {
        static $socket = null;

        // Create connection
        if (!$socket) {

            $address = app()->taskWorker->getSocketName();
            $flag = $wait
                ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT
                : STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT;

            if (!($socket = stream_socket_client($address, $errorCode, $errorMsg, 30, $flag))) {
                // Try again in a sec if not async
                if ($wait) {
                    log_error(sprintf("Failed to create socket for %s: [%s] %s. Retrying in a sec...", $task->getName(), $errorCode, $errorMsg));
                    sleep(1);
                    return self::withWorker($task, $length, $wait);
                }
                return false;
            }

            // Set blocking mode
            stream_set_blocking($socket, $wait);

            // Set buffer sizes
            stream_set_read_buffer($socket, $length);
            stream_set_write_buffer($socket, $length);

            // Set read/write timeout
            stream_set_timeout($socket, 30);
        }

        // Send the data
        $body = strval($task->getRequest(!$wait, app()->config->secret));
        for ($written = 0; $written < strlen($body); $written += $length) {
            $fwrite = fwrite($socket, substr($body, $written));
            if ($fwrite === false) {
                continue;
            }
        }

        // Receive the response.
        $response = $wait ? fread($socket, $length) : null;
        // Close socket
        !$wait && $socket && fclose($socket) && $socket = null;
        // Yield empty response. 
        return $wait && $response ? unserialize($response) : null;
    }
}
