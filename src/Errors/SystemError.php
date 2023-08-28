<?php

namespace Armie\Errors;

use Armie\Exceptions\HttpException;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class SystemError extends HttpException
{
    /**
     * @param string $message
     * @param int    $errorCode
     */
    public function __construct(string $message, int $errorCode = 1000)
    {
        parent::__construct($message, 500, $errorCode);
    }
}
