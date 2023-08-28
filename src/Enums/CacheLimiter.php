<?php

namespace Armie\Enums;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
enum CacheLimiter: string
{
    case NO_CACHE = 'nocache';
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case PRIVATE_NO_EXPIRE = 'private_no_expire';
}
