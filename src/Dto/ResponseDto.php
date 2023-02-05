<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ResponseDto extends BaseDto
{
    /**  @var bool */
    public bool $success;
    /** @var string */
    public string|null $message;
    /** @var object|array */
    public object|array|null $data;
    /** @var string */
    public string|null $env;
    /** @var string */
    public string|null $ip;
    /** @var string */
    public string|null $version;
    /** @var int */
    public int|null $duration;
    /** @var string */
    public string|null $errorCode;
    /** @var string */
    public string|null $errorLine;
    /** @var string */
    public string|null $errorFile;
    /** @var ErrorTraceDto[]|array */
    public array|null $errorTrace;

    /**
     * Set the value of success
     *
     * @return  self
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Set the value of message
     *
     * @return  self
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the value of data
     *
     * @return  self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the value of env
     *
     * @return  self
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Set the value of version
     *
     * @return  self
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set the value of duration
     *
     * @return  self
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Set the value of errorCode
     *
     * @return  self
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Set the value of errorLine
     *
     * @return  self
     */
    public function setErrorLine($errorLine)
    {
        $this->errorLine = $errorLine;

        return $this;
    }

    /**
     * Set the value of errorFile
     *
     * @return  self
     */
    public function setErrorFile($errorFile)
    {
        $this->errorFile = $errorFile;

        return $this;
    }

    /**
     * Set the value of errorTrace
     *
     * @return  self
     */
    public function setErrorTrace(array $errorTrace)
    {
        $this->errorTrace = $errorTrace;

        return $this;
    }
}
