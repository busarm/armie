<?php

namespace Busarm\PhpMini;

use Closure;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Traits\Container;

/**
 * Application Routes Provider
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Route implements RouteInterface
{
    use Container;

    /** @var Closure Request executable function */
    protected Closure|null $callable = null;

    /** @var string Request controller */
    protected string|null $controller = null;

    /** @var string Request controller function*/
    protected string|null $function = null;

    /** @var array<string, mixed> Request controller function params */
    protected array $params = [];

    /** @var string HTTP request method */
    protected string|null $method = null;

    /** @var string HTTP request path */
    protected string|null $path = null;

    /** @var MiddlewareInterface[] */
    protected array $middlewares = [];

    private function __construct()
    {
    }

    /**  @return Closure|null */
    public function getCallable(): Closure|null
    {
        return $this->callable;
    }
    /**  @return string */
    public function getController(): string|null
    {
        return $this->controller;
    }
    /**  @return string */
    public function getFunction(): string|null
    {
        return $this->function;
    }
    /**  @return array */
    public function getParams(): array
    {
        return $this->params;
    }
    /**  @return string */
    public function getMethod(): string|null
    {
        return $this->method;
    }
    /**  @return string */
    public function getPath(): string|null
    {
        return $this->path;
    }
    /**  @return MiddlewareInterface[] */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Add route params.
     * @param array<string, mixed> $params
     * List of key => value params. 
     * Where:
     * - `key` = function paramater name 
     * - `value` =  function paramater value
     * @return self 
     */
    public function params(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Set callable route destination
     * 
     * @param Closure $callable Function to execute for route
     * @return self
     */
    public function call(Closure $callable): self
    {
        $this->callable = $callable;
        $this->controller = null;
        $this->function = null;
        return $this;
    }

    /**
     * Set controller route destination
     * 
     * @param string $controller Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @return self
     */
    public function to(string $controller, string $function): self
    {
        $this->controller = $controller;
        $this->function = $function;
        $this->callable = null;
        return $this;
    }

    /**
     * Add route middlewares
     * 
     * @param MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function middlewares(array $middlewares = []): self
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * Add route middleware
     * 
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function middleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Set CLI route
     *
     * @param string $controller
     * @param string $function
     * @param array<string, mixed> $params
     * @return self
     */
    public static function cli(string $controller, string $function, array $params = []): self
    {
        $route = new Route;
        $route->controller = $controller;
        $route->function = $function;
        $route->params = $params;
        return $route;
    }

    /**
     * Set HTTP GET route
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function get(string $path): RouteInterface
    {
        $route = new Route;
        $route->method = HttpMethod::GET;
        $route->path = $path;
        return $route;
    }

    /**
     * Set HTTP POST route
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function post(string $path): RouteInterface
    {
        $route = new Route;
        $route->method = HttpMethod::POST;
        $route->path = $path;
        return $route;
    }

    /**
     * Set HTTP PUT route
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function put(string $path): RouteInterface
    {
        $route = new Route;
        $route->method = HttpMethod::PUT;
        $route->path = $path;
        return $route;
    }

    /**
     * Set HTTP PATCH route
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function patch(string $path): RouteInterface
    {
        $route = new Route;
        $route->method = HttpMethod::PATCH;
        $route->path = $path;
        return $route;
    }

    /**
     * Set HTTP DELETE route
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function delete(string $path): RouteInterface
    {
        $route = new Route;
        $route->method = HttpMethod::DELETE;
        $route->path = $path;
        return $route;
    }

    /**
     * Set HTTP HEAD route
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function head(string $path): RouteInterface
    {
        $route = new Route;
        $route->method = HttpMethod::HEAD;
        $route->path = $path;
        return $route;
    }
}
