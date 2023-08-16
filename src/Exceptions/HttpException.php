<?php

namespace Armie\Exceptions;

use Armie\App;
use Armie\Dto\ErrorTraceDto;
use Armie\Dto\ResponseDto;
use Armie\Enums\Env;
use Armie\Interfaces\ResponseInterface;
use Armie\Response;
use Exception;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class HttpException extends Exception
{
    private $statusCode;

    /**
     * @param string $message
     * @param integer $statusCode
     * @param integer $errorCode
     */
    public function __construct($message, int $statusCode, int $errorCode = 0)
    {
        parent::__construct($message, $errorCode);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Error handler
     * 
     * @param App $app
     * @return ResponseInterface
     */
    public function handler(App $app): ResponseInterface
    {
        $this->getStatusCode() >= 500 and $app->reporter->exception($this);
        return (new Response)->json(ResponseDto::fromError($this, $app->env, $app->config->version)->toArray(), $this->getStatusCode());
    }
}
