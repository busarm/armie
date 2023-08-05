<?php

namespace Busarm\PhpMini\Enums;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum SameSite: string
{
    case LAX       =   'Lax';
    case STRICT    =   'Strict';
    case NONE      =   'None';
}
