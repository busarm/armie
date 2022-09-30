<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Enums\ResponseFormat;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Nyholm\Psr7\Uri;

/**
 * Server Instance for handling multi tenancy
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Server
{
    /**
     * @var App[]
     */
    private $routeApps = [];
    /**
     * @var string[]
     */
    private $routePaths = [];
    /**
     * @var App[]
     */
    private $domainApps = [];
    /**
     * @var string[]
     */
    private $domainPaths = [];

    /**
     * Map route to a particular app. (Not recommended for production)
     *
     * @param string $route Route path to app. e.g v1
     * @param App $app
     * @return self
     */
    public function addRouteApp($route, App $app): self
    {
        $this->routeApps[$route] = $app;
        return $this;
    }

    /**
     * Map route to a particular app path
     *
     * @param string $route Route path to app. e.g v1
     * @param string $path Path to app index. e.g ../myapp/public/index.php or ../myapp/public.
     * If path is a directory, '/index.php' will be appended to the path
     * @return self
     */
    public function addRoutePath($route, $path): self
    {
        $this->routePaths[$route] = $path;
        return $this;
    }

    /**
     * Map list of route to a particular app path
     *
     * @param array $list Array of `$route => $path`. see `self::addRoutePath`
     * @return self
     */
    public function addRoutePathList(array $list): self
    {
        $this->routePaths = array_merge($this->routePaths, $list);
        return $this;
    }

    /**
     * Map domain to a particular app. (Not recommended for production)
     *
     * @param string $domain Domain name. e.g myap.com, dev.myapp.com
     * @param App $app
     * @return self
     */
    public function addDomainApp($domain, App $app): self
    {
        $this->domainApps[$domain] = $app;
        return $this;
    }

    /**
     * Map domain to a particular app path
     *
     * @param string $domain Domain name. e.g myap.com, dev.myapp.com
     * @param string $path Path to app index. e.g ../myapp/public/index.php or ../myapp/public.
     * If path is a directory, '/index.php' will be appended to the path
     * @return self
     */
    public function addDomainPath($domain, $path): self
    {
        $this->domainPaths[$domain] = $path;
        return $this;
    }

    /**
     * Map list of domain to a particular app path
     *
     * @param array $list Array of `$domain => $path`. see `self::addDomainPath`
     * @return self
     */
    public function addDomainPathList(array $list): self
    {
        $this->domainPaths = array_merge($this->domainPaths, $list);
        return $this;
    }

    /**
     * Run server
     *
     * @param ServerRequestInterface|null $request
     * @return \Psr\Http\Message\ResponseInterface|bool True if successful. ResponseInterface if failed
     */
    public function run(ServerRequestInterface|null $psr = null): MessageResponseInterface|bool
    {
        $request = $psr ? Request::fromPsr($psr) : Request::fromGlobal();
        if ($this->runRoute($request) !== false) {
            return true;
        } else if ($this->runDomain($request) !== false) {
            return true;
        }
        return (new Response())->setStatusCode(404)->toPsr(ResponseFormat::HTML);
    }

    /**
     * Run for route
     * 
     * @param RequestInterface $request
     * @return ResponseInterface|bool|null False if failed
     */
    protected function runRoute(RequestInterface $request): ResponseInterface|bool|null
    {
        $segments = $request->segments();

        for ($i = 0; $i < count($segments); $i++) {
            $route = implode('/', array_slice($segments, 0, $i + 1));

            // Check route apps
            if (array_key_exists($route, $this->routeApps)) {
                $uri = implode('/', array_slice($segments, $i + 1, count($segments)));
                return $this->routeApps[$route]->run($request->withUri(new Uri($request->baseUrl() . '/' . $uri)));
            }

            // Check route paths
            if (array_key_exists($route, $this->routePaths)) {
                $path = $this->routePaths[$route];
                $path = is_dir($path) ? $path . '/index.php' : $path;
                if (!file_exists($path)) {
                    throw new SystemError("App file not found: $path");
                }

                $uri = implode('/', array_slice($segments, $i + 1, count($segments)));
                return Loader::require($path, ['request' => $request->withUri(new Uri($request->baseUrl() . '/' . $uri))]);
            }
        }

        return false;
    }

    /**
     * Run for domain
     * 
     * @param RequestInterface $request
     * @return ResponseInterface|false|null False if failed
     */
    protected function runDomain(RequestInterface $request): ResponseInterface|bool|null
    {
        $domain = $request->domain();

        // Check domain apps
        if (array_key_exists($domain, $this->domainApps)) {
            return $this->domainApps[$domain]->run();
        }

        // Check domain paths
        if (array_key_exists($domain, $this->domainPaths)) {
            $path = $this->domainPaths[$domain];
            $path = is_dir($path) ? $path . '/index.php' : $path;
            if (!file_exists($path)) {
                throw new SystemError("App file not found: $path");
            }

            return Loader::require($path, ['request' => $request]);
        }

        return false;
    }
}
