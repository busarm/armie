<?php

namespace Armie\Interfaces;

use Armie\Enums\HttpMethod;
use Armie\Interfaces\StorageBagInterface;
use Armie\Interfaces\SessionStoreInterface;
use Armie\Interfaces\Resolver\AuthResolver;
use Armie\Interfaces\Resolver\ServerConnectionResolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
    public function requestId();

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
     * @return HttpMethod
     */
    public function method(): HttpMethod;

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
     * @return ?SessionStoreInterface Returns NULL if session is not enabled
     */
    public function session(): ?SessionStoreInterface;

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
     * @return AuthResolver|null
     */
    public function auth(): AuthResolver|null;

    /**
     * @return ServerConnectionResolver|null
     */
    public function connection(): ServerConnectionResolver|null;

    /**
     * Set the value of session
     *
     * @return  self
     */
    public function setSession(SessionStoreInterface $session): self;

    /**
     * Set the value of request
     *
     * @return  self
     */
    public function setRequest(StorageBagInterface $request): self;

    /**
     * Set the value of query
     *
     * @return  self
     */
    public function setQuery(StorageBagInterface $query): self;

    /**
     * Set the value of server
     *
     * @return  self
     */
    public function setServer(StorageBagInterface $server): self;

    /**
     * Set the value of files
     *
     * @return  self
     */
    public function setFiles(UploadBagInterface|StorageBagInterface $files): self;

    /**
     * Set the value of cookies
     *
     * @return  self
     */
    public function setCookies(StorageBagInterface $cookies): self;

    /**
     * Set the value of headers
     *
     * @return  self
     */
    public function setHeaders(StorageBagInterface $headers): self;

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setAuth(AuthResolver $auth): self;

    /**
     * Set the value of connection
     *
     * @return  self
     */
    public function setConnection(ServerConnectionResolver $connection): self;

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
