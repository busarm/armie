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
class NotFoundException extends HttpException
{
    public function __construct($message = 'Not found')
    {
        parent::__construct($message, 404);
    }
}
