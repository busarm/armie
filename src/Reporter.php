<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Interfaces\ReportingInterface;

use function Busarm\PhpMini\Helpers\log_debug;
use function Busarm\PhpMini\Helpers\log_error;
use function Busarm\PhpMini\Helpers\log_exception;

/**
 * Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Reporter implements ReportingInterface
{
    protected array $redactedParams = [
        'secret',
        'password',
        'authorization',
        'authentication',
        'confirm_password',
        'confirmpassword',
        'accesstoken',
        'access_token',
        'apikey',
        'api_key',
        'privatekey',
        'private_key',
    ];
    protected array $breadCrumbs = [];

    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param mixed $title
     * @param array $metadata
     * @return void
     */
    public function leaveCrumbs($title, array $metadata = [])
    {
        $this->breadCrumbs[$title] = $metadata;
    }

    /**
     * Get bread crumbs
     */
    public function getBreadCrumbs()
    {
        return $this->breadCrumbs;
    }

    /**
     * Report Info
     *
     * @param array $data
     * @return void
     */
    public function info(array $data)
    {
        log_debug($this->toString($this->redact($data)));
    }

    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public function error(string $heading, string $message, string|null $file = null, int|null $line = null)
    {
        $contexts = array_map(function ($instance) {
            return ($instance['file'] ?? $instance['class'] ?? '') . ':' . ($instance['line'] ?? '1');
        }, array_merge([['file' => $file, 'line' => $line]], debug_backtrace()));
        log_error($message);
        log_debug($this->toString([
            'crumbs' => $this->redact($this->breadCrumbs),
            'contexts' => $contexts,
        ]));
    }

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public function exception(\Throwable $exception)
    {
        $contexts = array_map(function ($instance) {
            return ($instance['file'] ?? $instance['class'] ?? '') . ':' . ($instance['line'] ?? '1');
        }, array_merge([['file' => $exception->getFile(), 'line' => $exception->getLine()]], $exception->getTrace()));
        log_exception($exception);
        log_debug($this->toString([
            'crumbs' => $this->redact($this->breadCrumbs),
            'contexts' => $contexts,
        ]));
    }


    /**
     * Add list of params to be redacted from report (LOWER CASED STRINGS)
     *
     * @param array<string> $list
     * @return void
     */
    public function addRedactedParams(array $list)
    {
        $this->redactedParams = array_merge($this->redactedParams, $list);
    }


    /**
     * Redact params
     *
     * @param array $params
     * @param array<string> $redactedParams
     * @return array
     */
    public function redact(array $params, $redactedParams = []): array
    {
        $redacted = [];
        $excluded = array_merge($this->redactedParams, $redactedParams);
        foreach ($params as $key => $value) {
            if ($value) {
                if (is_array($value) || is_object($value)) {
                    $redacted[$key] = $this->redact((array)$value, $redactedParams);
                } else if (in_array(strtolower($key), $excluded)) {
                    $redacted[$key] = "[REDACTED]";
                } else {
                    $redacted[$key] = $value;
                }
            } else {
                $redacted[$key] = $value;
            }
        }
        return $redacted;
    }

    /**
     * Array/Object to string
     *
     * @param array|object|null $msg
     * @return string|null
     */
    protected function toString(array|object|null $msg): string|null
    {
        if (is_array($msg) || is_object($msg)) {
            return json_encode($msg, JSON_PRETTY_PRINT);
        }
        return $msg;
    }
}
