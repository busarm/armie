<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;

use function Busarm\PhpMini\Helpers\is_cli;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Server
{
    /** @var static Server instance */
    public static $__instance;

    /** @var App|null Current app instance */
    public static App|null $__app;

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

    public function __construct()
    {
        self::$__instance = &$this;
    }

    /**
     * Map route to a particular app
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
        $path = is_dir($path) ? $path . '/index.php' : $path;
        if (!file_exists($path)) {
            throw new SystemError("App file not found: $path");
        }
        $this->routePaths[$route] = $path;
        return $this;
    }

    /**
     * Map domain to a particular app
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
     * Map route to a particular app path
     *
     * @param string $domain Domain name. e.g myap.com, dev.myapp.com
     * @param string $path Path to app index. e.g ../myapp/public/index.php or ../myapp/public.
     * If path is a directory, '/index.php' will be appended to the path
     * @return self
     */
    public function addDomainPath($domain, $path): self
    {
        $path = is_dir($path) ? $path . '/index.php' : $path;
        if (!file_exists($path)) {
            throw new SystemError("App file not found: $path");
        }
        $this->domainPaths[$domain] = $path;
        return $this;
    }

    /**
     * Run server
     *
     * @param RequestInterface|null $request Custom request object
     * @return ResponseInterface|bool True if successful. ResponseInterface if failed
     */
    public function run(RequestInterface|null $request = null)
    {
        $request = $request ?? Request::fromGlobal();
        if ($this->matchRoute($request) !== false) {
            return true;
        } else if ($this->matchDomain($request) !== false) {
            return true;
        }
        return (new Response())->html(false, 404);
    }

    /**
     * Match route
     * @param RequestInterface $request
     * @return ResponseInterface|bool|null False if failed
     */
    public function matchRoute(RequestInterface $request)
    {
        $segments = $request->segments();

        for ($i = 0; $i < count($segments); $i++) {
            $route = implode('/', array_slice($segments, 0, $i + 1));

            // Check route apps
            if (array_key_exists($route, $this->routeApps)) {
                $uri = implode('/', array_slice($segments, $i + 1, count($segments)));
                return $this->routeApps[$route]->run($request->withUrl($request->scheme(), $request->domain(), $uri));
            }

            // Check route paths
            if (array_key_exists($route, $this->routePaths)) {
                $uri = implode('/', array_slice($segments, $i + 1, count($segments)));
                $_SERVER['REQUEST_URI']     =   '/' . $uri;
                $_SERVER['PATH_INFO']       =   '/' . $uri;
                return include_once($this->routePaths[$route]);
            }
        }

        return false;
    }

    /**
     * Match domain
     * @param RequestInterface $request
     * @return ResponseInterface|false|null False if failed
     */
    public function matchDomain(RequestInterface $request)
    {
        $domain = $request->domain();

        // Check domain apps
        if (array_key_exists($domain, $this->domainApps)) {
            return $this->domainApps[$domain]->run();
        }

        // Check domain paths
        if (array_key_exists($domain, $this->domainPaths)) {
            if (is_cli()) {
                $_SERVER['HTTP_HOST']       =   $request->domain();
                $_SERVER['REQUEST_URI']     =   '/' . $request->uri();
                $_SERVER['PATH_INFO']       =   '/' . $request->uri();
                $_SERVER['REQUEST_METHOD']  =   $request->method();
            }
            return include_once($this->domainPaths[$domain]);
        }
        return false;
    }
}
