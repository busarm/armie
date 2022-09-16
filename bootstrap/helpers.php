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
 * Get Server Variable
 *
 * @param string $name
 * @param string $default
 * @return string
 */
function env($name, $default = null)
{
    return (!empty($data = @getenv($name)) ? $data : ($_SERVER[$name] ?? $default));
}

/**
 * Check if https enabled
 * @return bool
 */
function is_https()
{
    if (!empty(env('HTTPS')) && strtolower(env('HTTPS')) !== 'off') {
        return TRUE;
    } elseif (!empty(env('HTTP_X_FORWARDED_PROTO')) && strtolower(env('HTTP_X_FORWARDED_PROTO')) === 'https') {
        return TRUE;
    } elseif (!empty(env('HTTP_FRONT_END_HTTPS')) && strtolower(env('HTTP_FRONT_END_HTTPS')) !== 'off') {
        return TRUE;
    }
    return FALSE;
}


/**
 * Get Ip of users
 * @return string
 */
function get_ip_address()
{
    // check for shared internet/ISP IP
    if (!empty(env('HTTP_CLIENT_IP')) && validate_ip(env('HTTP_CLIENT_IP'))) {
        return env('HTTP_CLIENT_IP');
    }
    // check for IPs passing through proxies
    if (!empty(env('HTTP_X_FORWARDED_FOR'))) {
        // check if multiple ips exist in var
        if (strpos(env('HTTP_X_FORWARDED_FOR'), ',') !== false) {
            $iplist = explode(',', env('HTTP_X_FORWARDED_FOR') ?? '', 20);
            foreach ($iplist as $ip) {
                if (validate_ip($ip))
                    return $ip;
            }
        } else {
            if (validate_ip(env('HTTP_X_FORWARDED_FOR')))
                return env('HTTP_X_FORWARDED_FOR');
        }
    }
    if (!empty(env('HTTP_X_FORWARDED')) && validate_ip(env('HTTP_X_FORWARDED')))
        return env('HTTP_X_FORWARDED');

    if (!empty(env('HTTP_X_CLUSTER_CLIENT_IP')) && validate_ip(env('HTTP_X_CLUSTER_CLIENT_IP')))
        return env('HTTP_X_CLUSTER_CLIENT_IP');

    if (!empty(env('HTTP_FORWARDED_FOR')) && validate_ip(env('HTTP_FORWARDED_FOR')))
        return env('HTTP_FORWARDED_FOR');

    if (!empty(env('HTTP_FORWARDED')) && validate_ip(env('HTTP_FORWARDED')))
        return env('HTTP_FORWARDED');

    // return unreliable ip since all else failed
    return env('REMOTE_ADDR');
}

/**
 * Ensures an ip address is both a valid IP and does not fall within
 * a private network range.
 * @param $ip
 * @return bool
 */
function validate_ip($ip)
{
    if (strtolower($ip) === 'unknown') return false;

    // generate ipv4 network address
    $ip = ip2long($ip);

    // if the ip is set and not equivalent to 255.255.255.255
    if ($ip !== false && $ip !== -1) {

        // make sure to get unsigned long representation of ip
        // due to discrepancies between 32 and 64 bit OSes and
        // signed numbers (ints default to signed in PHP)
        $ip = sprintf('%u', $ip);

        // do private network range checking
        if ($ip >= 0 && $ip <= 50331647) return false;
        if ($ip >= 167772160 && $ip <= 184549375) return false;
        if ($ip >= 2130706432 && $ip <= 2147483647) return false;
        if ($ip >= 2851995648 && $ip <= 2852061183) return false;
        if ($ip >= 2886729728 && $ip <= 2887778303) return false;
        if ($ip >= 3221225984 && $ip <= 3221226239) return false;
        if ($ip >= 3232235520 && $ip <= 3232301055) return false;
        if ($ip >= 4294967040) return false;
    }

    return true;
}



/**
 * Get server protocol or http version
 * @return bool
 */
function get_server_protocol()
{
    return (!empty(env('SERVER_PROTOCOL')) && in_array(env('SERVER_PROTOCOL'), array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'), TRUE))
        ? env('SERVER_PROTOCOL') : 'HTTP/1.1';
}


/**
 * Print output end exit
 * @param mixed $data
 * @param int $responseCode
 */
function out($data = null, $responseCode = 500)
{
    if (!is_array($data) && !is_object($data)) {
        return is_cli() ? die(PHP_EOL . $data . PHP_EOL) : (new \Busarm\PhpMini\Response())->html($data, $responseCode);
    }
    return is_cli() ? die(PHP_EOL . var_export($data, true) . PHP_EOL) : (new \Busarm\PhpMini\Response())->json((array)$data, $responseCode, false);
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
 * Get app response object
 * @return \Busarm\PhpMini\Interfaces\ResponseInterface|mixed
 */
function &response()
{
    return app()->response;
}

/**
 * Get app request object
 * @return \Busarm\PhpMini\Interfaces\RequestInterface|mixed
 */
function &request()
{
    return app()->request;
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
 * @return \Busarm\PhpMini\Interfaces\ErrorReportingInterface
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
        return app()->logger->log($level, is_array($message) || is_object($message) ? var_export($message, true) : (string) $message, $context);
    } catch (\Throwable $th) {
        return (new ConsoleLogger(new ConsoleOutput()))->log($level, is_array($message) || is_object($message) ? var_export($message, true) : (string) $message, $context);
    }
}

/**
 * @param mixed $message
 */
function log_emergency($message)
{
    return log_message(\Psr\Log\LogLevel::EMERGENCY, $message);
}

/**
 * @param mixed $message
 */
function log_error($message)
{
    return log_message(\Psr\Log\LogLevel::ERROR, $message);
}

/**
 * @param Exception $exception
 */
function log_exception($exception)
{
    return log_message(\Psr\Log\LogLevel::ERROR, $exception->getMessage(), $exception->getTrace());
}

/**
 * @param mixed $message
 */
function log_info($message)
{
    return log_message(\Psr\Log\LogLevel::INFO, $message);
}

/**
 * @param mixed $message
 */
function log_debug($message)
{
    return log_message(\Psr\Log\LogLevel::DEBUG, $message);
}

/**
 * @param mixed $message
 */
function log_warning($message)
{
    return log_message(\Psr\Log\LogLevel::WARNING, $message);
}

/**
 * Run external command
 *
 * @param string $command
 * @param array $params
 * @param \Symfony\Component\Console\Output\OutputInterface $output
 * @param int $timeout Default = 600 seconds
 * @param boolean $wait Default = true
 * @return Symfony\Component\Process\Process
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
 * @return Symfony\Component\Process\Process
 */
function run_async(string $command, array $params, \Symfony\Component\Console\Output\OutputInterface $output, $timeout = 600)
{
    return run($command, $params, $output, $timeout, false);
}
