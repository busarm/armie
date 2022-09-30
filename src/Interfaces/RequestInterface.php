<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\Bags\AttributeBag;
use Busarm\PhpMini\Interfaces\Bags\SessionBag;
use Psr\Http\Message\UriInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface RequestInterface
{
    /**
     * @param UriInterface $uri
     * @return self
     */
    public function withUri(UriInterface $uri): self;

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
    public function domain();

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
     * @return mixed
     */
    public function content();


    /**
     * @return AttributeBag
     */
    public function file(): AttributeBag;

    /**
     * @return SessionBag
     */
    public function session(): SessionBag;

    /**
     * @return AttributeBag
     */
    public function cookie(): AttributeBag;

    /**
     * @return AttributeBag
     */
    public function query(): AttributeBag;

    /**
     * @return AttributeBag
     */
    public function request(): AttributeBag;

    /**
     * @return AttributeBag
     */
    public function server(): AttributeBag;

    /**
     * @return AttributeBag
     */
    public function header(): AttributeBag;

}
