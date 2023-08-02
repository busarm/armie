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
     * Default eventloop using `stream_select` and `pcntl_fork`
     */
    const DEFAULT   =   1;
    /**
     * Ev eventloop
     * Ensure `ev` extension is installed. E.g pecl install ev
     */
    const EV        =   2;
    /**
     * Libevent eventloop
     * Ensure `event` extension is installed. E.g pecl install event
     */
    const EVENT     =   3;
    /**
     * Swoole eventloop
     * Ensure `swoole` extension is installed. E.g pecl install swoole
     */
    const SWOOLE    =   4;
    /**
     * Lbbuv eventloop
     * Ensure `uv` extension is installed. E.g sudo apt-get install libuv1.dev for Ubuntu
     */
    const UV        =   5;
    /**
     * React eventloop
     * Ensure `react/event-loop` package is installed. E.g react/event-loop
     */
    const REACT     =   6;
}
