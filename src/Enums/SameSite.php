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
enum SameSite: string
{
    case LAX = 'Lax';
    case STRICT = 'Strict';
    case NONE = 'None';
}
