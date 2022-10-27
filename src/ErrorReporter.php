<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\ErrorReportingInterface;

use function Busarm\PhpMini\Helpers\log_debug;
use function Busarm\PhpMini\Helpers\log_error;
use function Busarm\PhpMini\Helpers\log_exception;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ErrorReporter implements ErrorReportingInterface
{
    protected array $breadCrumbs = [];

    /**
     * Set up error reporting
     *
     * @return void
     */
    public function setupReporting()
    {
        throw new SystemError('`setupReporting` not implemented');
    }

    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param mixed $title
     * @param array $metadata
     * @return void
     */
    public function leaveCrumbs($title, array $metadata = [])
    {
        $this->breadCrumbs[] = [
            'Title' => $title,
            'Metadata' => $metadata,
        ];
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
    public function reportError($heading, $message, $file = null, $line = null)
    {
        $contexts = [];
        if ($file) $contexts[] = $file . ':' . ($line ?? 0);
        log_error($message);
        log_debug($this->toString([
            'Crumbs' => $this->breadCrumbs,
            'Contexts' => $contexts,
        ]));
    }

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public function reportException($exception)
    {
        $contexts = array_map(function ($instance) {
            return ($instance['file'] ?? $instance['class'] ?? '') . ':' . ($instance['line'] ?? '0');
        }, $exception->getTrace());
        log_exception($exception);
        log_debug($this->toString([
            'Crumbs' => $this->breadCrumbs,
            'Contexts' => $contexts,
        ]));
    }

    /**
     * Array/Object to string
     *
     * @param array|object|null $msg
     * @return string|null
     */
    private function toString(array|object|null $msg): string|null
    {
        if (is_array($msg) || is_object($msg)) {
            return json_encode($msg, JSON_PRETTY_PRINT);
        }
        return $msg;
    }
}
