<?php

namespace Busarm\PhpMini\Enums;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum ResponseFormat: string
{
    case JSON  =   'json';
    case HTML  =   'html';
    case XML   =   'xml';
    case BIN   =   'bin';
}
