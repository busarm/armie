<?php

namespace Busarm\PhpMini\Enums;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum CacheLimiter: string
{
    case NO_CACHE          =   'nocache';
    case PUBLIC            =   'public';
    case PRIVATE           =   'private';
    case PRIVATE_NO_EXPIRE =   'private_no_expire';
}
