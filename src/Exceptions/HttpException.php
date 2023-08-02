<?php

namespace Busarm\PhpMini\Exceptions;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Dto\ErrorTraceDto;
use Busarm\PhpMini\Dto\ResponseDto;
use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Response;
use Exception;

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
        $this->getStatusCode() >= 500 and $app->reporter->reportException($this);
        return (new Response)->json(ResponseDto::fromError($this, $app->env, $app->config->version)->toArray(), $this->getStatusCode());
    }
}
