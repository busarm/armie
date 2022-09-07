<?php

namespace Busarm\PhpMini\Exceptions;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
class NotFoundException extends HttpException
{
    public function __construct($message = "Not found")
    {
        parent::__construct($message, 404);
    }
}
