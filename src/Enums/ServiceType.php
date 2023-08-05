<?php

namespace Busarm\PhpMini\Enums;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum ServiceType: string
{
    case CREATE    =  "CREATE";
    case READ      =  "READ";
    case UPDATE    =  "UPDATE";
    case DELETE    =  "DELETE";
}
