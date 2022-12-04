<?php

namespace Busarm\PhpMini\Helpers;

use Busarm\PhpMini\Errors\SystemError;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */


/**
 * Parses http query string into an array
 *
 * @author Alxcube <alxcube@gmail.com>
 *
 * @param string $queryString String to parse
 * @param string $argSeparator Query arguments separator
 * @param integer $decType Decoding type
 * @return array
 */
function http_parse_query($queryString, $argSeparator = '&', $decType = PHP_QUERY_RFC1738)
{
    $result             = array();
    $parts              = explode($argSeparator, $queryString);

    foreach ($parts as $part) {
        $partList = explode('=', $part, 2);
        if (count($partList) !== 2) continue;
        list($paramName, $paramValue)   = $partList;

        switch ($decType) {
            case PHP_QUERY_RFC3986:
                $paramName      = rawurldecode($paramName);
                $paramValue     = rawurldecode($paramValue);
                break;

            case PHP_QUERY_RFC1738:
            default:
                $paramName      = urldecode($paramName);
                $paramValue     = urldecode($paramValue);
                break;
        }


        if (preg_match_all('/\[([^\]]*)\]/m', $paramName, $matches)) {
            $paramName      = substr($paramName, 0, strpos($paramName, '['));
            $keys           = array_merge(array($paramName), $matches[1]);
        } else {
            $keys           = array($paramName);
        }

        $target         = &$result;

        foreach ($keys as $index) {
            if ($index === '') {
                if (isset($target)) {
                    if (is_array($target)) {
                        $intKeys        = array_filter(array_keys($target), 'is_int');
                        $index  = count($intKeys) ? max($intKeys) + 1 : 0;
                    } else {
                        $target = array($target);
                        $index  = 1;
                    }
                } else {
                    $target         = array();
                    $index          = 0;
                }
            } elseif (isset($target[$index]) && !is_array($target[$index])) {
                $target[$index] = array($target[$index]);
            }

            $target         = &$target[$index];
        }

        if (is_array($target)) {
            $target[]   = $paramValue;
        } else {
            $target     = $paramValue;
        }
    }

    return $result;
}

/**
 * Get Server Variable
 *
 * @param string $name
 * @param string $default
 * @return string
 */
function env($name, $default = null)
{
    $data = getenv($name) ?? false;
    return $data !== false ? $data : $default;
}

/**
 * Is CLI?
 *
 * Test to see if a request was made from the command line.
 *
 * @return 	bool
 */
function is_cli()
{
    return (PHP_SAPI === 'cli' or defined('STDIN'));
}

/**
 * Print output end exit
 * @param mixed $data
 * @param int $responseCode
 */
function out($data = null, $responseCode = 500)
{
    if (!is_array($data) && !is_object($data)) {
        return is_cli() ? die(PHP_EOL . $data . PHP_EOL) : (new \Busarm\PhpMini\Response())->html($data, $responseCode)->send(false);
    }
    return is_cli() ? die(PHP_EOL . var_export($data, true) . PHP_EOL) : (new \Busarm\PhpMini\Response())->json((array)$data, $responseCode)->send(false);
}

/**
 * Get current app instance
 * @return \Busarm\PhpMini\App
 */
function app(): \Busarm\PhpMini\App
{
    return \Busarm\PhpMini\App::$__instance ?? throw new SystemError('Failed to get current app instance');
}

/**
 * 
 * Get or Set config
 *
 * @param string $name
 * @param mixed $value
 * @return mixed
 */
function config($name, $value = null)
{
    return app()->config->get($name) ?? ($value ? app()->config->set($name, $value) : null);
}

/**
 * Load view file
 *
 * @param string $path
 * @param array $params
 * @param boolean $return Print out view or return content
 * @return mixed
 */
function view(string $path, $params = [], $return = false)
{
    return app()->loader->view($path, $params, $return);
}

/**
 * Get app loader object
 * @return \Busarm\PhpMini\Interfaces\LoaderInterface
 */
function &load()
{
    return app()->loader;
}

/**
 * Get app reporter object
 * @return \Busarm\PhpMini\Interfaces\ErrorReportingInterface
 */
function &report()
{
    return app()->reporter;
}

/**
 * Get app router object
 * @return \Busarm\PhpMini\Interfaces\RouterInterface
 */
function &router()
{
    return app()->router;
}

/**
 * @param string $level @see \Psr\Log\LogLevel
 * @param mixed $message
 * @param array $context
 */
function log_message($level, $message, array $context = [])
{
    try {
        app()->logger->log($level, is_array($message) || is_object($message) ? var_export($message, true) : (string) $message, $context);
    } catch (\Throwable $th) {
        (new ConsoleLogger(new ConsoleOutput()))->log($level, is_array($message) || is_object($message) ? var_export($message, true) : (string) $message, $context);
    }
}

/**
 * @param mixed $message
 */
function log_emergency($message)
{
    log_message(\Psr\Log\LogLevel::EMERGENCY, $message);
}

/**
 * @param mixed $message
 */
function log_error($message)
{
    log_message(\Psr\Log\LogLevel::ERROR, $message);
}

/**
 * @param \Exception $exception
 */
function log_exception($exception)
{
    log_message(\Psr\Log\LogLevel::ERROR, $exception->getMessage(), $exception->getTrace());
}

/**
 * @param mixed $message
 */
function log_info($message)
{
    log_message(\Psr\Log\LogLevel::INFO, $message);
}

/**
 * @param mixed $message
 */
function log_debug($message)
{
    log_message(\Psr\Log\LogLevel::DEBUG, $message);
}

/**
 * @param mixed $message
 */
function log_warning($message)
{
    log_message(\Psr\Log\LogLevel::WARNING, $message);
}

/**
 * Run external command
 *
 * @param string $command
 * @param array $params
 * @param \Symfony\Component\Console\Output\OutputInterface $output
 * @param int $timeout Default = 600 seconds
 * @param boolean $wait Default = true
 * @return \Symfony\Component\Process\Process
 */
function run(string $command, array $params, \Symfony\Component\Console\Output\OutputInterface $output, $timeout = 600, $wait = true)
{
    $output->getFormatter()->setStyle('error', new \Symfony\Component\Console\Formatter\OutputFormatterStyle('red'));
    $process = new \Symfony\Component\Process\Process([
        $command,
        ...array_filter($params, fn ($arg) => !empty($arg))
    ]);
    $process->setTimeout($timeout);
    if ($wait) {
        $process->run(function ($type, $data) use ($output) {
            if ($type == \Symfony\Component\Process\Process::ERR) {
                $output->writeln('<error>' . $data . '</error>');
            } else {
                $output->writeln('<comment>' . $data . '</comment>');
            }
        });
    } else {
        $process->start(function ($type, $data) use ($output) {
            if ($type == \Symfony\Component\Process\Process::ERR) {
                $output->writeln('<error>' . $data . '</error>');
            } else {
                $output->writeln('<comment>' . $data . '</comment>');
            }
        });
    }
    return $process;
}

/**
 * Run external command asynchronously
 *
 * @param string $command
 * @param array $params
 * @param \Symfony\Component\Console\Output\OutputInterface $output
 * @param int $timeout Default = 600 seconds
 * @return \Symfony\Component\Process\Process
 */
function run_async(string $command, array $params, \Symfony\Component\Console\Output\OutputInterface $output, $timeout = 600)
{
    return run($command, $params, $output, $timeout, false);
}

/**
 * Convert to proper unit
 * @param int|float $size
 * @return string
 */
function unit_convert($size)
{
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    $index = floor(log($size, 1024));
    return (round($size / pow(1024, ($index)), 2) ?? 0) . ' ' . $unit[$index] ?? '~';
}

/**
 * Check if any item in array validates to true
 *
 * @param array $list
 * @return boolean
 */
function any(array $list): bool
{
    return in_array(true, array_map(fn ($item) => !empty($item), $list));
}

/**
 * Check if all items in array validates to true
 *
 * @param array $list
 * @return boolean
 */
function all(array $list): bool
{
    return !in_array(false, array_map(fn ($item) => !empty($item), $list));
}

/**
 * Check if array is a list - [a,b,c] not [a=>1,b=>2,c=>3]
 *
 * @param array $list
 * @return boolean
 */
function is_list(array $list): bool
{
    return array_values($list) === $list;
}
