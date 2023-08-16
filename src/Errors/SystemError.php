<?php

namespace Armie\Errors;

use Armie\App;
use Armie\Dto\ErrorTraceDto;
use Armie\Dto\ResponseDto;
use Armie\Enums\Env;
use Armie\Exceptions\HttpException;
use Armie\Interfaces\ResponseInterface;
use Armie\Response;
use Error;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
