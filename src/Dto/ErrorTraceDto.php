<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ErrorTraceDto extends BaseDto
{
    /** @var string */
    public string|null $class;
    /** @var string */
    public string|null $line;
    /** @var string */
    public string|null $file;
    /** @var string */
    public string|null $function;

    /**
     * @param array $trace Instance of Error Trace
     * - file
     * - line
     * - class
     * - function
     */
    public function __construct($trace = [])
    {
        $this->file = $trace['file'] ?? null;
        $this->line = $trace['line'] ?? null;
        $this->class = $trace['class'] ?? null;
        $this->function = $trace['function'] ?? null;
    }
}
