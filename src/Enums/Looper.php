<?php

namespace Busarm\PhpMini\Enums;

/**
 * Event Looper types
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
class Looper
{
    /**
     * Ev eventloop
     * Ensure `ev` extension is installed. E.g pecl install ev
     */
    const EV        =   1;
    /**
     * Libevent eventloop
     * Ensure `event` extension is installed. E.g pecl install event
     */
    const EVENT     =   2;
    /**
     * Swoole eventloop
     * Ensure `swoole` extension is installed. E.g pecl install swoole
     */
    const SWOOLE    =   3;
    /**
     * Default eventloop using `stream_select`
     */
    const SELECT    =   4;
}
