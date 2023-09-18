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
        'authorization',
        'authentication',
        '.*secret.*',
        '.*password.*',
        '.*token',
        '.*key',
    ];

    protected array $breadCrumbs = [];

    /**
     * @inheritDoc
     */
    public function leaveCrumbs($title, array $metadata = [])
    {
        $this->breadCrumbs[$title] = $metadata;
    }

    /**
     * @inheritDoc
     */
    public function getBreadCrumbs(): array
    {
        return $this->breadCrumbs;
    }

    /**
     * @inheritDoc
     */
    public function info(array $data)
    {
        log_debug($this->toString($this->redact($data)));
    }

    /**
     * @inheritDoc
     */
    public function error(string $message)
    {
        $trace = array_map(
            function ($instance) {
                return $instance['file'] . ':' . ($instance['line'] ?? '1');
            },
            array_values(array_filter(debug_backtrace(), fn ($trace) => isset($trace['file'])))
        );
        log_error($message);
        log_debug($this->toString([
            'crumbs'   => $this->redact($this->breadCrumbs),
            'trace' => $trace,
        ]));
    }

    /**
     * @inheritDoc
     */
    public function exception(\Throwable $exception)
    {
        $trace = array_map(
            function ($instance) {
                return $instance['file'] . ':' . ($instance['line'] ?? '1');
            },
            array_values(array_filter($exception->getTrace(), fn ($trace) => isset($trace['file'])))
        );
        log_exception($exception);
        log_debug($this->toString([
            'crumbs'   => $this->redact($this->breadCrumbs),
            'trace' => $trace,
        ]));
    }

    /**
     * @inheritDoc
     */
    public function addRedactedParams(array $list)
    {
        $this->redactedParams = array_merge($this->redactedParams, $list);
    }

    /**
     * @inheritDoc
     */
    public function redact(array $params, $redactedParams = []): array
    {
        $replace = "[REDACTED]";
        $redacted = [];

        $excluded = array_map(function ($name) {
            return str_starts_with($name, '/') ? $name : sprintf("/(%s)/im", $name);
        }, array_merge($this->redactedParams, $redactedParams));

        foreach ($params as $key => $value) {
            if ($value) {
                if (is_array($value) || is_object($value)) {
                    $redacted[$key] = $this->redact((array) $value, $redactedParams);
                } else {
                    $redacted[$key] = preg_replace($excluded, $replace, $key) == $replace ? $replace : $value;
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
