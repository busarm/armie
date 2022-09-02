<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface RequestInterface
{
    /**
     * @return string
     */
    public function ip();
    
    /**
     * @return string
     */
    public function scheme();

    /**
     * @return string
     */
    public function host();

    /**
     * @return string
     */
    public function baseUrl();

    /**
     * @return string
     */
    public function uri();

    /**
     * @return array
     */
    public function segments();

    /**
     * @return string
     */
    public function currentUrl();

    /**
     * @return string
     */ 
    public function method();

    /**
     * @return string
     */ 
    public function contentType();

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function query($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function request($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function server($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function header($name, $default = null);

    /**
     * @return array
     */
    public function getQueryList();
    /**
     * @return array
     */
    public function getRequestList();
    /**
     * @return array
     */
    public function getServerList();
    /**
     * @return array
     */
    public function getHeaderList();
}
