<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Route;

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
     * @param RouteInterface $route 
     * @return self
     */
    public function addRoute(RouteInterface $route): self;

    /**
     * @param RouteInterface[] $route 
     * @return self
     */
    public function addRoutes(array $routes): self;

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
