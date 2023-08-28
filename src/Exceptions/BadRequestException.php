<?php

namespace Armie\Exceptions;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
class BadRequestException extends HttpException
{
    public function __construct($message = 'Invalid request')
    {
        parent::__construct($message, 400);
    }
}
