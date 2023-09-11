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
enum DataType: string
{
    case INT = 'int';
    case INTEGER = 'integer';
    case BOOL = 'bool';
    case BOOLEAN = 'boolean';
    case STRING = 'string';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case JSON = 'json';
    case FLOAT = 'float';
    case DATETIME = 'datetime';
    case MIXED = 'mixed';
}
