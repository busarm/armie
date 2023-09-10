<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface RouterInterface
{
    /**
     * @param string $method Http Method
     * @param string $path   Http Request Path
     *
     * @return RouteInterface
     */
    public function createRoute(string $method, string $path): RouteInterface;

    /**
     * Add single route.
     *
     * @param RouteInterface $route
     *
     * @return self
     */
    public function addRoute(RouteInterface $route): self;

    /**
     * Add list of routes.
     *
     * @param RouteInterface[] $routes
     *
     * @return self
     */
    public function addRoutes(array $routes): self;

    /**
     * Add Resource (CREATE/READ/UPDATE/DELETE) routes for controller.
     *
     * @param string $path       HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @param string $controller Application Controller class name e.g Home
     *
     * @return RouterInterface
     */
    public function addResourceRoutes(string $path, string $controller): RouterInterface;

    /**
     * @param string $method Http Method
     * @param string $path   Http Request Path
     *
     * @return ?RouteInterface
     */
    public function getRoute(string $method, string $path): ?RouteInterface;

    /**
     * @param string $method Http Method
     *
     * @return RouteInterface[]
     */
    public function getRoutes(string $method): array;

    /**
     * Process routing.
     *
     * @param RequestInterface|RouteInterface|null $request
     *
     * @return MiddlewareInterface[]
     */
    public function process(RequestInterface|RouteInterface|null $request = null): array;
}
