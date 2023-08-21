<?php

namespace Armie\Enums;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum Env: string
{
    case LOCAL     =   "local";
    case DEV       =   "development";
    case TEST      =   "testing";
    case STG       =   "staging";
    case UAT       =   "uat";
    case PROD      =   "production";
}
