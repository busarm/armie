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
enum Looper
{
    /**
     * Default eventloop using `stream_select` and `pcntl_fork`
     */
    case DEFAULT;
    /**
     * Ev eventloop
     * Ensure `ev` extension is installed. E.g pecl install ev
     */
    case EV;
    /**
     * Libevent eventloop
     * Ensure `event` extension is installed. E.g pecl install event
     */
    case EVENT;
    /**
     * Swoole eventloop
     * Ensure `swoole` extension is installed. E.g pecl install swoole
     */
    case SWOOLE;
    /**
     * Lbbuv eventloop
     * Ensure `uv` extension is installed. E.g sudo apt-get install libuv1.dev for Ubuntu
     */
    case UV;
    /**
     * React eventloop
     * Ensure `react/event-loop` package is installed. E.g react/event-loop
     */
    case REACT;
}
