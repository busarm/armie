<?php

namespace Busarm\PhpMini\Enums;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum Cron: string
{
    case EVERY_SECOND   = 'every-second';
    case EVERY_MINUTE   = 'every-minute';
    case HOURLY     = 'hourly';
    case DAILY      = 'daily';
    case WEEKLY     = 'weekly';
    case MONTHLY    = 'monthly';
    case YEARLY     = 'yearly';
}
