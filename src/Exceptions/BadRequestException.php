<?php

namespace Busarm\PhpMini\Exceptions;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class BadRequestException extends HttpException
{
    public function __construct($message = "Invalid request")
    {
        parent::__construct($message, 400);
    }
}
