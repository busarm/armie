<?php

namespace Busarm\PhpMini\Interfaces;

use Closure;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface RouteInterface
{
    /**  @return Closure|null */
    public function getCallable(): Closure|null;
    /**  @return string */
    public function getController(): string|null;
    /**  @return string */
    public function getFunction(): string|null;
    /**  @return array|null */
    public function getParams(): array|null;
    /**  @return string|null */
    public function getMethod(): string|null;
    /**  @return string|null */
    public function getPath(): string|null;
    /**  @return MiddlewareInterface[] */
    public function getMiddlewares(): array;
    
    /**
     * Set callable route destination
     * 
     * @param Closure $callable Function to execute for route
     * @return self
     */
    public function call(Closure $callable): self;

    /**
     * Set controller route destination
     * 
     * @param string $controller Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @return self
     */
    public function to(string $controller, string $function): self;

    /**
     * Add route middlewares
     * 
     * @param MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function middlewares(array $middlewares = []): self;

    /**
     * Add route middleware
     * 
     * @param MiddlewareInterface $middlewares
     * @return self
     */
    public function middleware(MiddlewareInterface $middleware): self;

    /**
     * Add route params.
     * List of key => value params. 
     * Where:
     * - `key` = function paramater name 
     * - `value` =  function paramater value
     * 
     * @return self 
     */
    public function params(array $params): self;
    
    /**
     * Set HTTP GET routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function get(string $path): RouteInterface;

    /**
     * Set HTTP POST routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function post(string $path): RouteInterface;

    /**
     * Set HTTP PUT routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function put(string $path): RouteInterface;

    /**
     * Set HTTP PATCH routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function patch(string $path): RouteInterface;

    /**
     * Set HTTP DELETE routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function delete(string $path): RouteInterface;

    /**
     * Set HTTP HEAD routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function head(string $path): RouteInterface;
}
