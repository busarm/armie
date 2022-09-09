<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Enums\RouteMatcher;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Middlewares\CallableRouteMiddleware;
use Busarm\PhpMini\Middlewares\ControllerRouteMiddleware;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Router implements RouterInterface
{
    const PATH_EXCLUDE_LIST = ["$", "<", ">", "[", "]", "{", "}", "^", "\\", "|", "%"];
    const ESCAPE_LIST = [
        "/" => "\/",
        "." => "\."
    ];
    const MATCHER_REGX = [
        "/\(" . RouteMatcher::ALPHA . "\)/" => "([a-zA-Z]+)",
        "/\(" . RouteMatcher::ALPHA_NUM . "\)/" => "([a-zA-Z-_]+)",
        "/\(" . RouteMatcher::ALPHA_NUM_DASH . "\)/" => "([a-zA-Z0-9-_]+)",
        "/\(" . RouteMatcher::NUM . "\)/" => "([0-9]+)",
        "/\(" . RouteMatcher::ANY . "\)/" => "(.+)"
    ];

    /**
     * Use to match route path to an exact variable name. e.g $uid = /user/{uid}
     */
    const PARAM_NAME_REGX = [
        "/\{\w*\}/" => "([a-zA-Z0-9-_]+)"
    ];

    /** @var string Request Controller */
    protected string|null $controller = null;

    /** @var string Request Function */
    protected string|null $function = null;

    /** @var string Request Params */
    protected array|null $params = [];

    /** @var string HTTP request host */
    protected string|null $requestHost = null;

    /** @var string HTTP request method */
    protected string|null $requestMethod = null;

    /** @var string HTTP request route */
    protected string|null $requestPath = null;

    /** @var bool If router is for HTTP request*/
    protected bool $isHttp = false;

    /** @var RouteInterface Current HTTP route */
    protected RouteInterface|null $currentRoute = null;

    /** @var RouteInterface[] HTTP routes */
    protected array $routes = [];

    protected function __construct()
    {
    }

    /**
     * @return self
     */
    public static function withRequest(RequestInterface $request): self
    {
        $router = new self;
        $router->requestMethod = $request->method();
        $router->requestPath = $request->uri();
        $router->isHttp = true;
        return $router;
    }

    /**
     * @param string $controller
     * @param string $function
     * @param array $params
     * @return self
     */
    public static function withController($controller, $function, $params = []): self
    {
        $router = new self;
        $router->controller = $controller;
        $router->function = $function;
        $router->params = $params;
        $router->isHttp = false;
        return $router;
    }

    /**
     * @return string|null
     */
    public function getRequestHost(): string|null
    {
        return $this->requestHost;
    }

    /**
     * @return string|null
     */
    public function getRequestMethod(): string|null
    {
        return $this->requestMethod;
    }

    /**
     * @return string|null
     */
    public function getRequestPath(): string|null
    {
        return $this->requestPath;
    }

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return RouteInterface|null
     */
    public function getCurrentRoute(): RouteInterface|null
    {
        return $this->currentRoute;
    }

    /**
     * @return boolean
     */
    public function getIsHttp()
    {
        return $this->isHttp;
    }

    /**
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->requestPath = $path;
        return $this;
    }

    /**
     * Process routing
     * @return MiddlewareInterface[]
     */
    public function process(): array
    {
        // If custom routes
        if ($this->controller && $this->function) {
            $routeMiddleware[] = new ControllerRouteMiddleware($this->controller, $this->function, $this->params);
            return $routeMiddleware;
        }
        // If http routes
        else {
            foreach ($this->routes as $route) {
                // Find route
                if (
                    strtoupper($route->getMethod()) == strtoupper($this->requestMethod) &&
                    ($params = $this->isMatch($this->requestPath, $route->getPath()))
                ) {
                    // Set current route
                    $this->currentRoute = is_array($params) ? $route->withParams($params) : $route;
                    // Callable
                    if ($callable = $this->currentRoute->getCallable()) {
                        $routeMiddleware = $this->currentRoute->getMiddlewares() ?? [];
                        $routeMiddleware[] = new CallableRouteMiddleware($callable, $this->currentRoute->getParams());
                        return $routeMiddleware;
                    }
                    // Controller
                    else {
                        $routeMiddleware = $route->getMiddlewares() ?? [];
                        $routeMiddleware[] = new ControllerRouteMiddleware($this->currentRoute->getController(), $this->currentRoute->getFunction(), $this->currentRoute->getParams());
                        return $routeMiddleware;
                    }
                }
            }
        }
        return [];
    }

    /**
     * @param Route $route 
     * @return RouterInterface
     */
    public function addRoute(RouteInterface $route): RouterInterface
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * @param Route[] $route 
     * @return RouterInterface
     */
    public function addRoutes(array $routes): RouterInterface
    {
        $this->routes = array_merge($this->routes, $routes);
        return $this;
    }

    /**
     * Check if path matches
     *
     * @param string $path Request path
     * @param string $route Route to compare to
     * @param boolean $startsWith path starts with route
     * @param boolean $startsWith path ends with route
     * @return boolean|array
     */
    public function isMatch($path, $route, $startsWith = true, $endsWith = true)
    {
        // Trim leading & trailing slash and spaces
        $route = trim($route, " /\t\n\r");
        $path = trim($path, " /\t\n\r");
        // Decode url
        $path = urldecode($path);
        // Remove unwanted characters from path
        $path = str_replace(self::PATH_EXCLUDE_LIST, "", $path, $excludeCount);
        if ($excludeCount > 0) throw new BadRequestException(sprintf("The following charaters are not allowed in the url: %s", implode(',', array_values(self::PATH_EXCLUDE_LIST))));
        // Escape charaters to be a safe Regexp
        $route = str_replace(array_keys(self::ESCAPE_LIST), array_values(self::ESCAPE_LIST), $route);
        // Replace matching keywords with regexp 
        $route = preg_replace(array_keys(self::MATCHER_REGX), array_values(self::MATCHER_REGX), $route);
        // Replace matching parameters keywords with regexp 
        $route = $this->createMatchParamsRoute($route, $paramMatches);
        // Search request path against route
        $result = preg_match($startsWith ? ($endsWith ? "/^$route$/i" : "/^$route/i") : ($endsWith ? "/$route$/i" : "/$route/i"), $path, $matches);
        if (!empty($path) && $result >= 1) {
            if (!empty($paramMatches)) {
                $params = array_combine($paramMatches, array_splice($matches, 1));
            } else $params = array_splice($matches, 1);
            return !empty($params) ? $params : true;
        }
        return false;
    }

    /**
     * Create route to be used for params matching
     *
     * @param string $route
     * @param array $matches
     * @return string New route for regexp matching
     */
    protected function createMatchParamsRoute($route, &$paramMatches = [])
    {
        $count = 0;
        $regxList = array_values(self::PARAM_NAME_REGX);
        return preg_replace_callback(array_keys(self::PARAM_NAME_REGX), function ($match) use ($count, &$paramMatches, $regxList) {
            $paramMatches[] = str_replace(['{', '}'], ['', ''], ($match[0] ?? $match));
            $replace = $regxList[$count] ?? '';
            ++$count;
            return $replace;
        }, $route, -1, $count);
    }
}
