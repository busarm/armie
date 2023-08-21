<?php

namespace Armie\Errors;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
class SessionError extends SystemError
{
    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 1001);
    }
}
