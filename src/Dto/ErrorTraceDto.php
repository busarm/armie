<?php

namespace Armie\Dto; 

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ErrorTraceDto extends BaseDto
{
    /** @var string */
    public string|null $class;
    /** @var int */
    public int|null $line;
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

    /**
     * Set the value of class
     *
     * @return  self
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Set the value of line
     *
     * @return  self
     */
    public function setLine($line)
    {
        $this->line = $line;

        return $this;
    }

    /**
     * Set the value of file
     *
     * @return  self
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Set the value of function
     *
     * @return  self
     */
    public function setFunction($function)
    {
        $this->function = $function;

        return $this;
    }
}
