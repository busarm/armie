<?php

namespace Busarm\PhpMini\Exceptions;

use Exception;

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class HttpException extends Exception
{
    private $statusCode;

    /**
     * @param string $message
     * @param integer $statusCode
     * @param integer $errorCode
     */
    public function __construct($message, int $statusCode, $errorCode = 0)
    {
        parent::__construct($message, $errorCode);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Exception handler
     *
     * @return void
     */
    public function handler()
    {
        if ($this->getStatusCode() >= 500) app()->reporter->reportException($this);
        $trace = array_map(function ($instance) {
            return [
                'file' => $instance['file'] ?? null,
                'line' => $instance['line'] ?? null,
                'class' => $instance['class'] ?? null,
                'function' => $instance['function'] ?? null,
            ];
        }, $this->getTrace());
        app()->showMessage($this->getStatusCode(), $this->getMessage(), $this->getCode(), $this->getLine(), $this->getFile(), $trace);
    }
}
