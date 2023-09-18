<?php

namespace Armie\Helpers;

use Armie\Async;
use Armie\Errors\SystemError;
use Armie\Interfaces\Promise\PromiseFinal;
use Armie\Interfaces\Promise\PromiseThen;
use Armie\Interfaces\Runnable;
use Armie\Promise;
use Armie\Tasks\CallableTask;
use Armie\Tasks\Task;
use Closure;
use Generator;
use Laravel\SerializableClosure\SerializableClosure;
use ReflectionObject;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */

//######### FEATURE HELPERS ############

/**
 * Convert to proper unit.
 *
 * @param int|float $size
 *
 * @return string
 */
function unit_convert($size)
{
    $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
    $index = floor(log($size, 1024));

    return (round($size / pow(1024, $index), 2) ?? 0) . ' ' . $unit[$index] ?? '~';
}

/**
 * Parses http query string into an array.
 *
 * @author Alxcube <alxcube@gmail.com>
 *
 * @param string $queryString  String to parse
 * @param string $argSeparator Query arguments separator
 * @param int    $decType      Decoding type
 *
 * @return array
 */
function http_parse_query($queryString, $argSeparator = '&', $decType = PHP_QUERY_RFC1738)
{
    $result = [];
    $parts = explode($argSeparator, $queryString);

    foreach ($parts as $part) {
        $partList = explode('=', $part, 2);
        if (count($partList) !== 2) {
            continue;
        }
        list($paramName, $paramValue) = $partList;

        switch ($decType) {
            case PHP_QUERY_RFC3986:
                $paramName = rawurldecode($paramName);
                $paramValue = rawurldecode($paramValue);
                break;

            case PHP_QUERY_RFC1738:
            default:
                $paramName = urldecode($paramName);
                $paramValue = urldecode($paramValue);
                break;
        }

        if (preg_match_all('/\[([^\]]*)\]/m', $paramName, $matches)) {
            $paramName = substr($paramName, 0, strpos($paramName, '['));
            $keys = array_merge([$paramName], $matches[1]);
        } else {
            $keys = [$paramName];
        }

        $target = &$result;

        foreach ($keys as $index) {
            if ($index === '') {
                if (isset($target)) {
                    if (is_array($target)) {
                        $intKeys = array_filter(array_keys($target), 'is_int');
                        $index = count($intKeys) ? max($intKeys) + 1 : 0;
                    } else {
                        $target = [$target];
                        $index = 1;
                    }
                } else {
                    $target = [];
                    $index = 0;
                }
            } elseif (isset($target[$index]) && !is_array($target[$index])) {
                $target[$index] = [$target[$index]];
            }

            $target = &$target[$index];
        }

        if (is_array($target)) {
            $target[] = $paramValue;
        } else {
            $target = $paramValue;
        }
    }

    return $result;
}

/**
 * Get Server Variable.
 *
 * @param string $name
 * @param string $default
 *
 * @return string
 */
function env($name, $default = null)
{
    return $_ENV[$name] ?? (getenv($name, true) ?: $default);
}

/**
 * Is CLI?
 *
 * Test to see if a request was made from the command line.
 *
 * @return bool
 */
function is_cli()
{
    return PHP_SAPI === 'cli' or defined('STDIN');
}

/**
 * Print output end exit.
 *
 * @param mixed $data
 * @param int   $responseCode
 */
function out($data = null, $responseCode = 500)
{
    if (!is_array($data) && !is_object($data)) {
        return is_cli() ? exit(PHP_EOL . $data . PHP_EOL) : (new \Armie\Response())->html($data, $responseCode)->send(false);
    }

    return is_cli() ? exit(PHP_EOL . var_export($data, true) . PHP_EOL) : (new \Armie\Response())->json((array) $data, $responseCode)->send(false);
}

//######### APPLICATION HELPERS ############

/**
 * Get current app instance.
 *
 * @return \Armie\App
 */
function app(): \Armie\App
{
    return \Armie\App::getInstance() ?? throw new SystemError('Failed to get current app instance');
}

/**
 * Get or Set custom config.
 *
 * @param string $name
 * @param mixed  $value
 *
 * @return mixed
 */
function config($name, $value = null)
{
    try {
        return app()->config->get($name) ?? ($value ? app()->config->set($name, $value) : null);
    } catch (\Throwable) {
        return null;
    }
}

/**
 * Load view file.
 *
 * @param string $path
 * @param array  $params
 * @param bool   $return Print out view or return content
 *
 * @return mixed
 */
function view(string $path, $params = [], $return = false)
{
    return app()->loader->view($path, $params, $return);
}

/**
 * Get app loader object.
 *
 * @return \Armie\Interfaces\LoaderInterface
 */
function &load()
{
    return app()->loader;
}

/**
 * Get app reporter object.
 *
 * @return \Armie\Interfaces\ReportingInterface
 */
function &report()
{
    return app()->reporter;
}

/**
 * Get app router object.
 *
 * @return \Armie\Interfaces\RouterInterface
 */
function &router()
{
    return app()->router;
}

/**
 * @param string $level   @see \Psr\Log\LogLevel
 * @param mixed  $message
 * @param array  $context
 */
function log_message($level, $message, array $context = [])
{
    $message = print_r($message, true);
    $message = sprintf(
        '%s.%s - %s',
        date('Y-m-d H:i:s', time()),
        substr(gettimeofday()['usec'] ?? '0000', 0, 4),
        $message
    );

    try {
        $prefix = app()->getWorker() ? sprintf('%s (#%s)', app()->getWorker()->name, app()->getWorker()->id) : app()->config->name;
        $message = $prefix . ' - ' . $message;
        app()->logger->log($level, $message, $context);
    } catch (\Throwable) {
        (new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)))->log($level, $message, $context);
    }
}

/**
 * @param mixed $message
 */
function log_emergency(...$message)
{
    foreach ($message as $log) {
        log_message(\Psr\Log\LogLevel::EMERGENCY, $log);
    }
}

/**
 * @param mixed $message
 */
function log_error(...$message)
{
    foreach ($message as $log) {
        log_message(\Psr\Log\LogLevel::ERROR, $log);
    }
}

/**
 * @param \Throwable $exception
 */
function log_exception(\Throwable $exception)
{
    log_message(
        \Psr\Log\LogLevel::ERROR,
        sprintf('%s in %s:%s', $exception->getMessage() ?: 'Exception', $exception->getFile(), $exception->getLine() ?? 1)
    );
}

/**
 * @param mixed $message
 */
function log_info(...$message)
{
    foreach ($message as $log) {
        log_message(\Psr\Log\LogLevel::INFO, $log);
    }
}

/**
 * @param mixed $message
 */
function log_debug(...$message)
{
    foreach ($message as $log) {
        log_message(\Psr\Log\LogLevel::DEBUG, $log);
    }
}

/**
 * @param mixed $message
 */
function log_warning(...$message)
{
    foreach ($message as $log) {
        log_message(\Psr\Log\LogLevel::WARNING, $log);
    }
}

/**
 * @param mixed $message
 */
function log_notice(...$message)
{
    foreach ($message as $log) {
        log_message(\Psr\Log\LogLevel::NOTICE, $log);
    }
}

/**
 * Run external command.
 *
 * @param string $command
 * @param array  $params
 * @param int    $timeout Default = 600 seconds
 * @param bool   $wait    Default = true
 *
 * @return \Symfony\Component\Process\Process
 */
function run(string $command, array $params = [], $timeout = 600, $wait = true)
{
    $process = new Process([
        $command,
        ...array_filter($params, fn ($arg) => !empty($arg)),
    ]);
    $process->setTimeout($timeout);
    if ($wait) {
        $process->run(function ($type, $data) {
            if ($type == Process::ERR) {
                log_error($data);
            } else {
                log_debug($data);
            }
        });
    } else {
        $process->start(function ($type, $data) {
            if ($type == Process::ERR) {
                log_error($data);
            } else {
                log_debug($data);
            }
        });
    }

    return $process;
}

/**
 * Run external command asynchronously.
 *
 * @param string $command
 * @param array  $params
 * @param int    $timeout Default = 600 seconds
 *
 * @return \Symfony\Component\Process\Process
 */
function run_async(string $command, array $params = [], $timeout = 600)
{
    return run($command, $params, $timeout, false);
}

//########## ARRAY HELPERS #################

/**
 * Check if any item in array validates to `true`.
 *
 * @param array $list
 *
 * @return bool
 */
function any(array $list): bool
{
    return in_array(true, array_map(fn ($item) => !empty($item), $list));
}

/**
 * Check if all items in array validates to `true`.
 *
 * @param array $list
 *
 * @return bool
 */
function all(array $list): bool
{
    return !in_array(false, array_map(fn ($item) => !empty($item), $list));
}

/**
 * Find item in list by checking against predicate function.
 *
 * @param callable $fn   Predicate function. Return `true` if matched, else `false`
 * @param array    $list List to check
 *
 * @return mixed found item or `null` if failed
 */
function find(callable $fn, array $list): mixed
{
    foreach ($list as $item) {
        if ($fn($item) != false) {
            return $item;
        }
    }

    return null;
}

/**
 * Create 'Set-Cookie' header value.
 *
 * @param string $name
 * @param string $value
 * @param int    $expires
 * @param string $path
 * @param string $domain
 * @param string $samesite
 * @param bool   $secure
 * @param bool   $httponly
 *
 * @return string
 */
function create_cookie_header(
    string $name,
    string $value,
    int $expires = 0,
    string $path = '',
    string $domain = '',
    string $samesite = '',
    bool $secure = false,
    bool $httponly = false
): string {
    $value = rawurlencode($value);
    $date = date('D, d-M-Y H:i:s', $expires) . ' GMT';
    $header = "{$name}={$value}";
    if ($expires != 0) {
        $header .= "; Expires={$date}; Max-Age=" . ($expires - time());
    }
    if ($path != '') {
        $header .= '; Path=' . $path;
    }
    if ($domain != '') {
        $header .= '; Domain=' . $domain;
    }
    if ($samesite != '') {
        $header .= '; SameSite=' . $samesite;
    }
    if ($secure) {
        $header .= '; Secure';
    }
    if ($httponly) {
        $header .= '; HttpOnly';
    }

    return $header;
}

/**
 * Find a free port on the system.
 *
 * @return int
 */
function find_free_port()
{
    $sock = socket_create_listen(0);
    socket_getsockname($sock, $addr, $port);
    socket_close($sock);

    return $port;
}

/**
 * This function returns the maximum files size that can be uploaded
 * in PHP.
 *
 * @return int size in kilobytes
 **/
function get_max_upload_size($default = 1024)
{
    return min(parse_php_size(ini_get('post_max_size')), parse_php_size(ini_get('upload_max_filesize'))) ?: $default * 1024;
}

/**
 * This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case).
 *
 * @param string $sSize
 *
 * @return int The value in bytes
 */
function parse_php_size($sSize)
{
    $sSuffix = strtoupper(substr($sSize, -1));
    if (!in_array($sSuffix, ['P', 'T', 'G', 'M', 'K'])) {
        return (int) $sSize;
    }
    $iValue = substr($sSize, 0, -1);
    switch ($sSuffix) {
        case 'P':
            $iValue *= 1024;
            // Fallthrough intended
        case 'T':
            $iValue *= 1024;
            // Fallthrough intended
        case 'G':
            $iValue *= 1024;
            // Fallthrough intended
        case 'M':
            $iValue *= 1024;
            // Fallthrough intended
        case 'K':
            $iValue *= 1024;
            break;
    }

    return (int) $iValue;
}

/**
 * Resolve Promise.
 *
 * @param Promise<T>|PromiseThen<T>|PromiseFinal $promise
 *
 * @return T
 *
 * @template T
 */
function await(Promise|PromiseThen|PromiseFinal $promise): mixed
{
    return Promise::resolve($promise);
}

/**
 * Run task asynchronously.
 *
 * @param Task|callable $task             Task to process
 * @param bool          $useEventLoopOnly Force to process using event loop only
 */
function async(Task|callable $task, bool $useEventLoopOnly = false): void
{
    $useEventLoopOnly ? Async::runTask($task) : Async::withEventLoop($task);
}

/**
 * Run task list concurrently.
 *
 * @param array<Task<T>|callable():T> $task
 * @param bool                      $wait
 * 
 * @return array<T>
 * @template T
 */
function concurrent(array $tasks): array
{
    return Async::runTasks($tasks, true);
}

/**
 * Listen to event.
 *
 * @param string                          $event
 * @param callable|class-string<Runnable> $listner
 */
function listen(string $event, callable|string $listner)
{
    app()->eventHandler?->listen($event, $listner);
}

/**
 * Dispatch event.
 *
 * @param string $event
 * @param array  $data
 */
function dispatch(string $event, array $data = [])
{
    app()->eventHandler?->dispatch($event, $data);
}

/**
 * Queue task.
 *
 * @param Task|callable                        $task
 * @param callable|class-string<Runnable>|null $listner
 */
function enqueue(Task|callable $task, callable|string|null $listner = null)
{
    if (!app()->queueHandler) {
        throw new SystemError('Queue handler is not set. @see App::setQueueHandler');
    }

    $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));
    app()->queueHandler->enqueue($task, $listner);
}

/**
 * Wrap data to be serialized.
 *
 * @param mixed $data
 *
 * @return mixed
 */
function wrap_serializable($data)
{
    if ($data instanceof Closure) {
        $data = new SerializableClosure($data);
    } elseif (is_callable($data)) {
        $data = new SerializableClosure(Closure::fromCallable($data));
    } elseif (is_array($data)) {
        $data = array_map(function ($value) {
            return wrap_serializable($value);
        }, $data);
    } elseif (is_object($data)) {
        $reflection = new ReflectionObject($data);
        do {
            if ($reflection->isUserDefined()) {
                foreach ($reflection->getProperties() as $prop) {
                    if (
                        !$prop->isStatic()
                        && !$prop->isReadOnly()
                        && $prop->getDeclaringClass()->isUserDefined()
                        && $prop->isInitialized($data)
                    ) {
                        $value = $prop->getValue($data);
                        if (isset($value)) {
                            $prop->setValue($data, wrap_serializable($value));
                        }
                    }
                }
            }
        } while ($reflection = $reflection->getParentClass());
    }

    return $data;
}

/**
 * Unwrap data that was unserialized.
 *
 * @param mixed $data
 *
 * @return mixed
 */
function unwrap_serializable($data)
{
    if ($data instanceof SerializableClosure) {
        $data = $data->getClosure();
    } elseif (is_array($data)) {
        $data = array_map(function ($value) {
            return unwrap_serializable($value);
        }, $data);
    } elseif (is_object($data)) {
        $reflection = new ReflectionObject($data);
        do {
            if ($reflection->isUserDefined()) {
                foreach ($reflection->getProperties() as $prop) {
                    if (
                        !$prop->isStatic()
                        && !$prop->isReadOnly()
                        && $prop->getDeclaringClass()->isUserDefined()
                        && $prop->isInitialized($data)
                    ) {
                        $value = $prop->getValue($data);
                        if (isset($value)) {
                            $prop->setValue($data, unwrap_serializable($value));
                        }
                    }
                }
            } else {
                break;
            }
        } while ($reflection = $reflection->getParentClass());
    }

    return $data;
}

/**
 * Serialize.
 *
 * @param mixed $data
 *
 * @return string
 */
function serialize($data)
{
    return \serialize(wrap_serializable($data));
}

/**
 * Unserialize.
 *
 * @param string     $data
 * @param array|null $options
 *
 * @return mixed
 */
function unserialize($data, array $options = [])
{
    return unwrap_serializable(\unserialize($data, $options));
}

/**
 * Open Internet or Unix domain socket connection to a client
 *
 * @param string    $address Client address. Unix socket file path (`unix://<path>`) or TCP endpoint (`tcp://<domain>:<port>`)
 * @param bool      $block   Blocking or Non-Blocking connection
 * @param bool      $persist Peristent connection
 * @param int       $timeout Connection timeout in seconds. Default: 10secs
 * @param resource  $context Stream context. @see stream_context_create.
 *
 * @throws SystemError If failed and $block = true
 *
 * @return resource|false Return connection or `false` if failed and $async = true
 */
function stream_connect(string $address, bool $block = true, bool $persist = false, int $timeout = 10, mixed $context = NULL): mixed
{
    static $connection = null;

    $flag = $block
        ? ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT : STREAM_CLIENT_CONNECT)
        : ($persist ? STREAM_CLIENT_PERSISTENT | STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT : STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

    if ($persist && $connection) {
        $socket = $connection;
    } elseif (!($socket = stream_socket_client($address, $errorCode, $errorMsg, $timeout, $flag, $context))) {
        if ($block) {
            throw new SystemError(sprintf('Failed to connect to %: [%s] %s.', $address, $errorCode, $errorMsg));
        }
        return false;
    }

    // Set blocking mode
    stream_set_blocking($socket, $block);

    // Set read/write timeout
    stream_set_timeout($socket, $timeout);

    if ($persist) {
        $connection = $socket;
    }

    return $socket;
}

/**
 * Read from stream.
 *
 * @param resource $resource
 * @param int      $length
 *
 * @return string
 */
function stream_read(mixed $resource, int $length = 8192): string
{
    $response = '';
    while (!feof($resource)) {
        $response .= fread($resource, $length);
        $stream_meta_data = stream_get_meta_data($resource);
        if (($stream_meta_data['unread_bytes'] ?? 0) <= 0) {
            break;
        }
    }

    return $response;
}

/**
 * Write to stream.
 *
 * @param resource $resource
 * @param string   $data
 * @param int      $length
 *
 * @return bool
 */
function stream_write(mixed $resource, string $data, int $length = 8192): bool
{
    $fwrite = $length;
    for ($written = 0; $written < strlen($data); $written += $fwrite) {
        $fwrite = fwrite($resource, substr($data, $written));
        if ($fwrite === false) {
            return false;
        }
    }

    return true;
}

/**
 * Get text for error level.
 */
function error_level(int $level): string
{
    switch ($level) {
        case E_ERROR: // 1 //
            return 'E_ERROR';

        case E_WARNING: // 2 //
            return 'E_WARNING';

        case E_PARSE: // 4 //
            return 'E_PARSE';

        case E_NOTICE: // 8 //
            return 'E_NOTICE';

        case E_CORE_ERROR: // 16 //
            return 'E_CORE_ERROR';

        case E_CORE_WARNING: // 32 //
            return 'E_CORE_WARNING';

        case E_COMPILE_ERROR: // 64 //
            return 'E_COMPILE_ERROR';

        case E_COMPILE_WARNING: // 128 //
            return 'E_COMPILE_WARNING';

        case E_USER_ERROR: // 256 //
            return 'E_USER_ERROR';

        case E_USER_WARNING: // 512 //
            return 'E_USER_WARNING';

        case E_USER_NOTICE: // 1024 //
            return 'E_USER_NOTICE';

        case E_STRICT: // 2048 //
            return 'E_STRICT';

        case E_RECOVERABLE_ERROR: // 4096 //
            return 'E_RECOVERABLE_ERROR';

        case E_DEPRECATED: // 8192 //
            return 'E_DEPRECATED';

        case E_USER_DEPRECATED: // 16384 //
            return 'E_USER_DEPRECATED';
    }

    return '';
}

/**
 * Itterate a range of elements.
 *
 * @param float|int|string $start First value of the sequence.
 * @param float|int|string $end The sequence is ended upon reaching the `end` value.
 * @param float|int $step If a `step` value is given, it will be used as the increment (or decrement) between elements in the sequence. `step` must not equal `0` and must not exceed the specified range. If not specified, `step` will default to 1.
 * @return Generator Returns an itterator of elements from `start` to `end`, inclusive.
 */
function range_itterate(float|int|string $start, float|int|string $end, float|int $step = 1): Generator
{
    foreach (range($start, $end, $step) as $index) {
        yield $index;
    }
}

/**
 * Itterate a list of elements.
 *
 * @param array $list
 * @return Generator Returns an itterator of elements from `start` to `end`, inclusive.
 */
function array_itterate(array $list): Generator
{
    foreach ($list as $key => $value) {
        yield $key => $value;
    }
}
