<?php

namespace Busarm\PhpMini\Enums;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum Env: string
{
    case LOCAL     =   "local";
    case DEV       =   "development";
    case TEST      =   "testing";
    case STG       =   "staging";
    case PROD      =   "production";
}
