<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Busarm\PhpMini\Interfaces\SessionStoreInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface RequestInterface extends ContainerInterface
{
    /**
     * @return string
     */
    public function correlationId();

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
    public function currentUrl();

    /**
     * @return string
     */
    public function path();

    /**
     * @return array
     */
    public function segments();

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
     * @return string
     */
    public function version();

    /**
     * @return UploadBagInterface|StorageBagInterface
     */
    public function file(): UploadBagInterface|StorageBagInterface;

    /**
     * @return SessionStoreInterface
     */
    public function session(): SessionStoreInterface;

    /**
     * @return StorageBagInterface
     */
    public function cookie(): StorageBagInterface;

    /**
     * @return StorageBagInterface
     */
    public function query(): StorageBagInterface;

    /**
     * @return StorageBagInterface
     */
    public function request(): StorageBagInterface;

    /**
     * @return StorageBagInterface
     */
    public function server(): StorageBagInterface;

    /**
     * @return StorageBagInterface
     */
    public function header(): StorageBagInterface;

    /**
     * Set the value of session
     *
     * @return  self
     */
    public function setSession(SessionStoreInterface $session);

    /**
     * Set the value of request
     *
     * @return  self
     */
    public function setRequest(StorageBagInterface $request);

    /**
     * Set the value of query
     *
     * @return  self
     */
    public function setQuery(StorageBagInterface $query);

    /**
     * Set the value of server
     *
     * @return  self
     */
    public function setServer(StorageBagInterface $server);

    /**
     * Set the value of files
     *
     * @return  self
     */
    public function setFiles(UploadBagInterface|StorageBagInterface $files);

    /**
     * Set the value of cookies
     *
     * @return  self
     */
    public function setCookies(StorageBagInterface $cookies);

    /**
     * Set the value of headers
     *
     * @return  self
     */
    public function setHeaders(StorageBagInterface $headers);

    /**
     * @param UriInterface $uri
     * @return self
     */
    public function withUri(UriInterface $uri): self;

    /**
     * @return ServerRequestInterface
     */
    public function toPsr(): ServerRequestInterface;
}
