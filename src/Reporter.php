<?php

namespace Armie;

use Armie\Interfaces\ReportingInterface;

use function Armie\Helpers\log_debug;
use function Armie\Helpers\log_error;
use function Armie\Helpers\log_exception;

/**
 * Reporting.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
     * Leave breadcrumbs for issue tracking.
     *
     * @param mixed $title
     * @param array $metadata
     *
     * @return void
     */
    public function leaveCrumbs($title, array $metadata = [])
    {
        $this->breadCrumbs[$title] = $metadata;
    }

    /**
     * Get bread crumbs.
     */
    public function getBreadCrumbs()
    {
        return $this->breadCrumbs;
    }

    /**
     * Report Info.
     *
     * @param array $data
     *
     * @return void
     */
    public function info(array $data)
    {
        log_debug($this->toString($this->redact($data)));
    }

    /**
     * Report Error.
     *
     * @param string $message
     *
     * @return void
     */
    public function error(string $message)
    {
        $contexts = array_map(
            function ($instance) {
                return $instance['file'] . ':' . ($instance['line'] ?? '1');
            },
            array_values(array_filter(debug_backtrace(), fn ($trace) => isset($trace['file'])))
        );
        log_error($message);
        log_debug($this->toString([
            'crumbs'   => $this->redact($this->breadCrumbs),
            'contexts' => $contexts,
        ]));
    }

    /**
     * Report Exception.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    public function exception(\Throwable $exception)
    {
        $contexts = array_map(
            function ($instance) {
                return $instance['file'] . ':' . ($instance['line'] ?? '1');
            },
            array_values(array_filter($exception->getTrace(), fn ($trace) => isset($trace['file'])))
        );
        log_exception($exception);
        log_debug($this->toString([
            'crumbs'   => $this->redact($this->breadCrumbs),
            'contexts' => $contexts,
        ]));
    }

    /**
     * Add list of params to be redacted from report (LOWER CASED STRINGS).
     *
     * @param array<string> $list
     *
     * @return void
     */
    public function addRedactedParams(array $list)
    {
        $this->redactedParams = array_merge($this->redactedParams, $list);
    }

    /**
     * Redact params.
     *
     * @param array         $params
     * @param array<string> $redactedParams
     *
     * @return array
     */
    public function redact(array $params, $redactedParams = []): array
    {
        $redacted = [];
        $excluded = array_merge($this->redactedParams, $redactedParams);
        foreach ($params as $key => $value) {
            if ($value) {
                if (is_array($value) || is_object($value)) {
                    $redacted[$key] = $this->redact((array) $value, $redactedParams);
                } elseif (in_array(strtolower($key), $excluded)) {
                    $redacted[$key] = '[REDACTED]';
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
     * Array to String.
     *
     * @param array $data
     *
     * @return string
     */
    protected function toString(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
