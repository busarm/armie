<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\RouteMatcher;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\BadRequestException;
use Busarm\PhpMini\Helpers\Security;
use Busarm\PhpMini\Interfaces\Data\ResourceControllerInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Middlewares\CallableRouteMiddleware;
use Busarm\PhpMini\Middlewares\ControllerRouteMiddleware;
use Busarm\PhpMini\Middlewares\ViewRouteMiddleware;

/**
 * Application Router
 * 
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

    /** @var RouteInterface[] HTTP routes */
    protected array $routes = [];

    /**
     * [RESTRICTED]
     */
    public function __serialize()
    {
        throw new SystemError("Serializing router instance is forbidden");
    }

    /**
     * @param HttpMethod $method
     * @param string $path
     * @return RouteInterface
     */
    public function createRoute(HttpMethod $method, string $path): RouteInterface
    {
        $route = match ($method) {
            HttpMethod::GET     =>  Route::get($path),
            HttpMethod::POST    =>  Route::post($path),
            HttpMethod::PUT     =>  Route::put($path),
            HttpMethod::PATCH   =>  Route::patch($path),
            HttpMethod::DELETE  =>  Route::delete($path),
            HttpMethod::HEAD    =>  Route::head($path),
        };
        $this->routes[] = &$route;
        return $route;
    }

    /**
     * Add single route
     * 
     * @param RouteInterface $route 
     * @return RouterInterface
     */
    public function addRoute(RouteInterface $route): RouterInterface
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * Add list of routes
     * 
     * @param RouteInterface[] $routes
     * @return RouterInterface
     */
    public function addRoutes(array $routes): RouterInterface
    {
        $this->routes = array_merge($this->routes, $routes);
        return $this;
    }

    /**
     * Add Resource (CREATE/READ/UPDATE/DELETE) routes for controller. 
     * Controller must implement ResourceControllerInterface
     * 
     * @see ResourceControllerInterface
     * 
     * @param string $path HTTP path. e.g /home. @see RouteMatcher for list of parameters matching keywords
     * @param class-string<ResourceControllerInterface> $controller Application Controller class name e.g Home
     * @return RouterInterface
     */
    public function addResourceRoutes(string $path, string $controller): RouterInterface
    {

        if (!in_array(ResourceControllerInterface::class, class_implements($controller))) {
            throw new SystemError("`$controller` does not implement " . ResourceControllerInterface::class);
        }

        $this->createRoute(HttpMethod::GET, "$path/list")->to($controller, 'list');
        $this->createRoute(HttpMethod::GET, "$path/paginate")->to($controller, 'paginatedList');
        $this->createRoute(HttpMethod::GET, "$path/{id}")->to($controller, 'get');
        $this->createRoute(HttpMethod::POST, "$path/bulk")->to($controller, 'createBulk');
        $this->createRoute(HttpMethod::POST, $path)->to($controller, 'create');
        $this->createRoute(HttpMethod::PUT, "$path/bulk")->to($controller, 'updateBulk');
        $this->createRoute(HttpMethod::PUT, "$path/{id}")->to($controller, 'update');
        $this->createRoute(HttpMethod::DELETE, "$path/bulk")->to($controller, 'deleteBulk');
        $this->createRoute(HttpMethod::DELETE, "$path/{id}")->to($controller, 'delete');

        return $this;
    }

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * 
     * Process routing
     *
     * @param RequestInterface|RouteInterface|null $request
     * @return \Busarm\PhpMini\Interfaces\MiddlewareInterface[]
     */
    public function process(RequestInterface|RouteInterface|null $request = null): array
    {
        // If custom routes
        if ($request instanceof RouteInterface) {

            // View
            if ($view = $request->getView()) {
                $routeMiddleware = $request->getMiddlewares() ?? [];
                $routeMiddleware[] = new ViewRouteMiddleware($view, $request->getParams());
                return $routeMiddleware;
            }
            // Callable
            else if ($callable = $request->getCallable()) {
                $routeMiddleware = $request->getMiddlewares() ?? [];
                $routeMiddleware[] = new CallableRouteMiddleware($callable, $request->getParams());
                return $routeMiddleware;
            }
            // Controller
            else {
                $routeMiddleware = $request->getMiddlewares() ?? [];
                $routeMiddleware[] = new ControllerRouteMiddleware($request->getController(), $request->getFunction(), $request->getParams());
                return $routeMiddleware;
            }
        }

        // If http request
        else if ($request instanceof RequestInterface) {
            foreach ($this->routes as $route) {
                // Find route
                if (
                    $route->getMethod() === $request->method() &&
                    ($params = $this->isMatch($request->path(), $route->getPath())) !== false
                ) {
                    // Set route params
                    $route->params($params ?: []);

                    // View
                    if ($view = $route->getView()) {
                        $routeMiddleware = $route->getMiddlewares() ?? [];
                        $routeMiddleware[] = new ViewRouteMiddleware($view, $route->getParams());
                        return $routeMiddleware;
                    }
                    // Callable
                    if ($callable = $route->getCallable()) {
                        $routeMiddleware = $route->getMiddlewares() ?? [];
                        $routeMiddleware[] = new CallableRouteMiddleware($callable, $route->getParams());
                        return $routeMiddleware;
                    }
                    // Controller
                    else {
                        $routeMiddleware = $route->getMiddlewares() ?? [];
                        $routeMiddleware[] = new ControllerRouteMiddleware($route->getController(), $route->getFunction(), $route->getParams());
                        return $routeMiddleware;
                    }
                }
            }
        }

        return [];
    }

    /**
     * Check if path matches
     *
     * @param string $path Request path
     * @param string $route Route to compare to
     * @param boolean $startsWith path starts with route
     * @param boolean $startsWith path ends with route
     * @return array|false Return list of path match or `false` if failed
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
            return !empty($params) ? Security::cleanParams($params) : [];
        }
        return false;
    }

    /**
     * Create route to be used for params matching
     *
     * @param string $route
     * @param array $paramMatches
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
