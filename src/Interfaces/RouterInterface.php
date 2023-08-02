<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface RouterInterface
{
    /**
     * @param string $method
     * @param string $path
     * @return RouteInterface
     */
    public function createRoute(string $method, string $path): RouteInterface;

    /**
     * Add single route
     * 
     * @param RouteInterface $route 
     * @return self
     */
    public function addRoute(RouteInterface $route): self;

    /**
     * Add list of routes
     * 
     * @param RouteInterface[] $routes
     * @return self
     */
    public function addRoutes(array $routes): self;

    /**
     * Add Resource (CREATE/READ/UPDATE/DELETE) routes for controller
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @param string $controller Application Controller class name e.g Home
     * @return RouterInterface
     */
    public function addResourceRoutes(string $path, string $controller): RouterInterface;

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * 
     * Process routing
     *
     * @param RequestInterface|RouteInterface|null $request
     * @return MiddlewareInterface[]
     */
    public function process(RequestInterface|RouteInterface|null $request = null): array;

    /**
     * Check if path matches
     *
     * @param string $path Request path
     * @param string $route Route to compare to
     * @param boolean $startsWith path starts with route
     * @param boolean $startsWith path ends with route
     * @return boolean|array
     */
    public function isMatch($path, $route, $startsWith = true, $endsWith = true);
}
