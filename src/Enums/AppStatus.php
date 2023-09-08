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
enum AppStatus
{
    case INITIALIZING;
    case STARTED;
    case RUNNNIG;
    case COMPLETED;
    case STOPPED;
}
