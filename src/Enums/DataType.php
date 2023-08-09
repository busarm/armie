<?php

namespace Busarm\PhpMini\Enums;

use Busarm\PhpMini\Helpers\StringableDateTime;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum DataType: string
{
    case INT       =   'int';
    case INTEGER   =   'integer';
    case BOOL      =   'bool';
    case BOOLEAN   =   'boolean';
    case STRING    =   'string';
    case ARRAY     =   'array';
    case OBJECT    =   'object';
    case JSON      =   'json';
    case FLOAT     =   'float';
    case DOUBLE    =   'double';
    case DATETIME  =   'datetime';
    case MIXED     =   'mixed';
}
