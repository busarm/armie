<?php

namespace Armie\Configs;

use Armie\Errors\SystemError;
use Armie\Interfaces\SocketControllerInterface;

/**
 * Async HTTP Server Configuration.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class HttpServerConfig extends TaskWorkerServerConfig
{
    /**
     * Server Public Url.
     */
    public ?string $serverUrl = null;

    /**
     * Number of http worker processes to spawn. Minimum = 1.
     */
    public int $httpWorkers = 2;

    /**
     * Max number of http requests to handle per worker before reloading worker. This is to prevent memory leak.
     */
    public int $httpMaxRequests = 10000;

    /**
     * Socket Connection Handlers.
     *
     * @var array<string,class-string<SocketControllerInterface>>
     */
    public array $sockets = [];

    /**
     * Set server public URL.
     *
     * @return static
     */
    public function setServerUrl($serverUrl)
    {
        $this->serverUrl = $serverUrl;

        return $this;
    }

    /**
     * Set number of http workers to spawn. Minimum = 1.
     *
     * @return static
     */
    public function setHttpWorkers(int $httpWorkers): static
    {
        $this->httpWorkers = $httpWorkers;

        return $this;
    }

    /**
     * Set max number of http requests to handle per worker before reloading worker. This is to prevent memory leak. Default: 10,000.
     *
     * @return static
     */
    public function setHttpMaxRequests(int $httpMaxRequests)
    {
        $this->httpMaxRequests = $httpMaxRequests;

        return $this;
    }

    /**
     * Add socket connection.
     *
     * @param int                                     $port       Socket port
     * @param class-string<SocketControllerInterface> $controller Socket controller class
     *
     * @return static
     */
    public function addSocket(int $port, string $controller): static
    {
        if (!is_subclass_of($controller, SocketControllerInterface::class)) {
            throw new SystemError("`$controller` does not implement " . SocketControllerInterface::class);
        }

        $port = (string) $port;
        $this->sockets[$port] = $controller;

        return $this;
    }
}
