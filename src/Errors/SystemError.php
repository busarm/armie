<?php

namespace Busarm\PhpMini\Errors;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Dto\ErrorTraceDto;
use Busarm\PhpMini\Dto\ResponseDto;
use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Exceptions\HttpException;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Response;
use Error;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class SystemError extends HttpException
{

    /**
     * @param string $message
     * @param integer $errorCode
     */
    public function __construct($message, int $errorCode = 0)
    {
        parent::__construct($message, 500, $errorCode);
    }
}
