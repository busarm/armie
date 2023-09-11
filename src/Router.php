<?php

namespace Armie;

use Armie\Enums\HttpMethod;
use Armie\Enums\RouteMatcher;
use Armie\Errors\SystemError;
use Armie\Exceptions\BadRequestException;
use Armie\Helpers\Security;
use Armie\Interfaces\Data\ResourceControllerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\RouteInterface;
use Armie\Interfaces\RouterInterface;
use Armie\Middlewares\CallableRouteMiddleware;
use Armie\Middlewares\ControllerRouteMiddleware;
use Armie\Middlewares\ViewRouteMiddleware;

/**
 * Application Router.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Router implements RouterInterface
{
    const PATH_EXCLUDE_LIST = ['$', '<', '>', '[', ']', '{', '}', '^', '\\', '|', '%'];
    const ESCAPE_LIST = [
        '/' => "\/",
        '.' => "\.",
    ];
    const MATCHER_REGX = [
        "/\(" . RouteMatcher::ALPHA . "\)/"          => '([a-zA-Z]+)',
        "/\(" . RouteMatcher::ALPHA_NUM . "\)/"      => '([a-zA-Z-_]+)',
        "/\(" . RouteMatcher::ALPHA_NUM_DASH . "\)/" => '([a-zA-Z0-9-_]+)',
        "/\(" . RouteMatcher::NUM . "\)/"            => '([0-9]+)',
        "/\(" . RouteMatcher::ANY . "\)/"            => '(.+)',
    ];

    /**
     * Use to match route path to an exact variable name. e.g $uid = /user/{uid}.
     */
    const PARAM_NAME_REGX = [
        "/\{\w*\}/" => '([a-zA-Z0-9-_]+)',
    ];

    /** @var array<string,RouteInterface[]> HTTP routes */
    protected array $routes = [];

    /**
     * [RESTRICTED].
     */
    public function __serialize()
    {
        throw new SystemError('Serializing router instance is forbidden');
    }

    /**
     * @inheritDoc
     */
    public function createRoute(string $method, string $path): RouteInterface
    {
        $method = strtoupper($method);

        $route = match ($method) {
            HttpMethod::GET->value     => Route::get($path),
            HttpMethod::POST->value    => Route::post($path),
            HttpMethod::PUT->value     => Route::put($path),
            HttpMethod::PATCH->value   => Route::patch($path),
            HttpMethod::DELETE->value  => Route::delete($path),
            HttpMethod::HEAD->value    => Route::head($path),
            default  => Route::get($path)
        };

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        return $this->routes[$method][strtolower($route->getPath())] = &$route;
    }

    /**
     * @inheritDoc
     */
    public function addRoute(RouteInterface $route): RouterInterface
    {
        if (!isset($this->routes[$route->getMethod()->value])) {
            $this->routes[$route->getMethod()->value] = [];
        }

        $this->routes[$route->getMethod()->value][strtolower($route->getPath())] = $route;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addRoutes(array $routes): RouterInterface
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addResourceRoutes(string $path, string $controller): RouterInterface
    {
        if (!in_array(ResourceControllerInterface::class, class_implements($controller))) {
            throw new SystemError("`$controller` does not implement " . ResourceControllerInterface::class);
        }

        $this->createRoute(HttpMethod::GET->value, "$path/list")->to($controller, 'list');
        $this->createRoute(HttpMethod::GET->value, "$path/paginate")->to($controller, 'paginatedList');
        $this->createRoute(HttpMethod::GET->value, "$path/{id}")->to($controller, 'get');
        $this->createRoute(HttpMethod::POST->value, "$path/bulk")->to($controller, 'createBulk');
        $this->createRoute(HttpMethod::POST->value, $path)->to($controller, 'create');
        $this->createRoute(HttpMethod::PUT->value, "$path/bulk")->to($controller, 'updateBulk');
        $this->createRoute(HttpMethod::PUT->value, "$path/{id}")->to($controller, 'update');
        $this->createRoute(HttpMethod::DELETE->value, "$path/bulk")->to($controller, 'deleteBulk');
        $this->createRoute(HttpMethod::DELETE->value, "$path/{id}")->to($controller, 'delete');

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(string $method, string $path): ?RouteInterface
    {
        $path = strtolower($path);
        $routes = $this->getRoutes($method);

        if (isset($routes[$path])) {
            return $routes[$path];
        }

        foreach ($routes as $route) {
            if (($params = $this->isMatch($path, strtolower($route->getPath()))) !== false) {
                $route->params(array_merge($route->getParams(), $params ?: []));
                return $route;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getRoutes(string $method): array
    {
        return $this->routes[strtoupper($method)] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function process(RequestInterface|RouteInterface|null $request = null): array
    {
        // If route request
        if ($request instanceof RouteInterface) {
            return $this->getMiddlewares($request);
        }
        // If http request
        elseif ($request instanceof RequestInterface) {
            if ($route = $this->getRoute($request->method()->value, $request->path())) {
                return $this->getMiddlewares($route);
            }
        }

        return [];
    }

    /**
     * Get route middlewares
     *
     * @param RouteInterface $route
     * @return array
     */
    public function getMiddlewares(RouteInterface $route): array
    {
        // View
        if ($view = $route->getView()) {
            $routeMiddleware = $route->getMiddlewares();
            $routeMiddleware[] = new ViewRouteMiddleware($view, $route->getParams());

            return $routeMiddleware;
        }
        // Callable
        if ($callable = $route->getCallable()) {
            $routeMiddleware = $route->getMiddlewares();
            $routeMiddleware[] = new CallableRouteMiddleware($callable, $route->getParams());

            return $routeMiddleware;
        }
        // Controller
        else {
            $routeMiddleware = $route->getMiddlewares();
            $routeMiddleware[] = new ControllerRouteMiddleware($route->getController(), $route->getFunction(), $route->getParams());

            return $routeMiddleware;
        }
    }

    /**
     * Match request path against route
     *
     * @param string  $path       Request path
     * @param string  $route      Route to compare to
     * @param bool    $startsWith Path starts with route
     * @param bool    $endsWith   Path ends with route
     *
     * @return array|false Return list of path param matches or `false` if failed
     */
    protected function isMatch(string $path, string $route, bool $startsWith = true, bool $endsWith = true)
    {
        // Trim leading & trailing slash and spaces
        $route = trim($route, " /\t\n\r");
        $path = trim(urldecode($path), " /\t\n\r");

        if ($route === $path) {
            return [];
        }
        if (empty($path)) {
            return false;
        }

        // Remove unwanted characters from path
        $path = str_replace(self::PATH_EXCLUDE_LIST, '', $path, $excludeCount);
        if ($excludeCount > 0) {
            throw new BadRequestException(
                sprintf(
                    'The following charaters are not allowed in the url: %s',
                    implode(',', array_values(self::PATH_EXCLUDE_LIST))
                )
            );
        }

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
            } else {
                $params = array_splice($matches, 1);
            }

            return !empty($params) ? Security::cleanParams($params) : [];
        }

        return false;
    }

    /**
     * Create route to be used for params matching.
     *
     * @param string $route
     * @param array  $paramMatches
     *
     * @return string New route for regexp matching
     */
    protected function createMatchParamsRoute($route, &$paramMatches = [])
    {
        $count = 0;
        $regxList = array_values(self::PARAM_NAME_REGX);

        return preg_replace_callback(array_keys(self::PARAM_NAME_REGX), function ($match) use ($count, &$paramMatches, $regxList) {
            $paramMatches[] = str_replace(['{', '}'], ['', ''], $match[0] ?? $match);
            $replace = $regxList[$count] ?? '';
            $count++;

            return $replace;
        }, $route, -1, $count);
    }
}
