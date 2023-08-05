<?php

namespace Busarm\PhpMini\Enums;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
enum HttpMethod: string
{
    case GET       =  "GET";
    case POST      =  "POST";
    case PUT       =  "PUT";
    case PATCH     =  "PATCH";
    case DELETE    =  "DELETE";
    case OPTIONS   =  "OPTIONS";
    case HEAD      =  "HEAD";
}
