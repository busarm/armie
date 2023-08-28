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
enum ResponseFormat: string
{
    case JSON = 'json';
    case HTML = 'html';
    case XML = 'xml';
    case BIN = 'bin';
}
