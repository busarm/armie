<?php

namespace Busarm\PhpMini\Helpers;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */

if (!function_exists('is_cli')) {
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
}

if (!function_exists('env')) {
    /**
     * Get Server Variable
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    function env($name, $default = null)
    {
        return (!empty($data = @getenv($name)) ? $data : $default);
    }
}

if (!function_exists('is_https')) {
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
}

if (!function_exists('get_ip_address')) {
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
                $iplist = explode(',', env('HTTP_X_FORWARDED_FOR'));
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
}

if (!function_exists('validate_ip')) {

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
}


if (!function_exists('get_server_protocol')) {

    /**
     * Get server protocol or http version
     * @return bool
     */
    function get_server_protocol()
    {
        return (!empty(env('SERVER_PROTOCOL')) && in_array(env('SERVER_PROTOCOL'), array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'), TRUE))
            ? env('SERVER_PROTOCOL') : 'HTTP/1.1';
    }
}

if (!function_exists('app')) {
    /**
     * Get app instance
     * @return \Busarm\PhpMini\App
     */
    function app(): \Busarm\PhpMini\App
    {
        return \Busarm\PhpMini\App::$__instance;
    }
}

if (!function_exists('config')) {
    /**
     * Get or Set config
     * @return mixed
     */
    function config($name, $value = null)
    {
        return app()->config($name, $value);
    }
}

if (!function_exists('view')) {
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
}

if (!function_exists('out')) {
    /**
     * Print output end exit
     * @param mixed $data
     */
    function out($data = null)
    {
        if (!is_array($data) && !is_object($data)) {
            return app()->response->html($data, 500);
        }
        return app()->response->json(json_decode(json_encode($data), true), 500, false);
    }
}

if (!function_exists('response')) {
    /**
     * Get app response object
     * @return \Busarm\PhpMini\Interfaces\ResponseInterface|mixed
     */
    function &response()
    {
        return app()->response;
    }
}

if (!function_exists('request')) {
    /**
     * Get app request object
     * @return \Busarm\PhpMini\Interfaces\RequestInterface|mixed
     */
    function &request()
    {
        return app()->request;
    }
}

if (!function_exists('load')) {
    /**
     * Get app loader object
     * @return \Busarm\PhpMini\Interfaces\LoaderInterface
     */
    function &load()
    {
        return app()->loader;
    }
}

if (!function_exists('report')) {
    /**
     * Get app reporter object
     * @return \Busarm\PhpMini\Interfaces\ErrorReportingInterface
     */
    function &report()
    {
        return app()->reporter;
    }
}

if (!function_exists('router')) {
    /**
     * Get app router object
     * @return \Busarm\PhpMini\Interfaces\ErrorReportingInterface
     */
    function &router()
    {
        return app()->router;
    }
}

if (!function_exists('log_emergency')) {
    /**
     * @param mixed $message
     */
    function log_emergency($message)
    {
        return app()->logger->emergency($message);
    }
}

if (!function_exists('log_error')) {
    /**
     * @param string $level @see \Psr\Log\LogLevel
     * @param mixed $message
     * @param array $context
     */
    function log_message($level, $message, array $context = [])
    {
        return app()->logger->log($level, is_array($message) || is_object($message) ? json_encode($message, JSON_PRETTY_PRINT) : (string) $message, $context);
    }
}

if (!function_exists('log_error')) {
    /**
     * @param mixed $message
     */
    function log_error($message)
    {
        return log_message(\Psr\Log\LogLevel::ERROR, $message);
    }
}

if (!function_exists('log_exception')) {
    /**
     * @param Exception $exception
     */
    function log_exception($exception)
    {
        return log_message(\Psr\Log\LogLevel::ERROR, $exception->getMessage(), $exception->getTrace());
    }
}

if (!function_exists('log_info')) {
    /**
     * @param mixed $message
     */
    function log_info($message)
    {
        return log_message(\Psr\Log\LogLevel::INFO, $message);
    }
}

if (!function_exists('log_debug')) {
    /**
     * @param mixed $message
     */
    function log_debug($message)
    {
        return log_message(\Psr\Log\LogLevel::DEBUG, $message);
    }
}

if (!function_exists('log_warning')) {
    /**
     * @param mixed $message
     */
    function log_warning($message)
    {
        return log_message(\Psr\Log\LogLevel::WARNING, $message);
    }
}

if (!function_exists('run')) {

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
}

if (!function_exists('run_async')) {

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
}
