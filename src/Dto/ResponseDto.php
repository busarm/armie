<?php

namespace Armie\Dto;

use Armie\Enums\Env;
use Throwable;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ResponseDto extends BaseDto
{
    /** @var bool */
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
    /** @var int */
    public int|null $errorLine;
    /** @var string */
    public string|null $errorFile;
    /** @var ErrorTraceDto[]|array */
    public array|null $errorTrace;

    /**
     * Set the value of success.
     *
     * @return self
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Set the value of message.
     *
     * @return self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the value of data.
     *
     * @return self
     */
    public function setData(object|array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the value of env.
     *
     * @return self
     */
    public function setEnv(string $env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Set the value of version.
     *
     * @return self
     */
    public function setVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set the value of duration.
     *
     * @return self
     */
    public function setDuration(int $duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Set the value of errorCode.
     *
     * @return self
     */
    public function setErrorCode(string $errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Set the value of errorLine.
     *
     * @return self
     */
    public function setErrorLine(int $errorLine)
    {
        $this->errorLine = $errorLine;

        return $this;
    }

    /**
     * Set the value of errorFile.
     *
     * @return self
     */
    public function setErrorFile(string $errorFile)
    {
        $this->errorFile = $errorFile;

        return $this;
    }

    /**
     * Set the value of errorTrace.
     *
     * @return self
     */
    public function setErrorTrace(array $errorTrace)
    {
        $this->errorTrace = $errorTrace;

        return $this;
    }

    /**
     * Initialize response dto with throwable.
     *
     * @param Throwable $e
     * @param Env       $env     App environment
     * @param string    $version App version
     *
     * @return self
     */
    public static function fromError(Throwable $e, Env $env, string $version): self
    {
        $trace = array_map(
            fn ($instance) => new ErrorTraceDto($instance),
            array_values(array_filter($e->getTrace(), fn ($trace) => isset($trace['file'])))
        );

        $response = new ResponseDto();
        $response->success = false;
        $response->message = $e->getMessage();
        $response->env = $env->value;
        $response->version = $version;

        // Show more info if not production
        if (!$response->success && $env !== Env::PROD) {
            $response->errorCode = strval($e->getCode());
            $response->errorLine = $e->getLine();
            $response->errorFile = $e->getFile();
            $response->errorTrace = !empty($trace) ? json_decode(json_encode($trace), 1) : null;
        }

        return $response;
    }
}
