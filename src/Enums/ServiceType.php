<?php

namespace Armie\Enums;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum ServiceType: string
{
    case CREATE    =  "CREATE";
    case READ      =  "READ";
    case UPDATE    =  "UPDATE";
    case DELETE    =  "DELETE";
}
