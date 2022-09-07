<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\MiddlewareInterface;

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
     * @return self
     */
    public function setPath(string $path): self;

    /**
     * @return boolean
     */ 
    public function getIsHttp();

    /**
     * @return string|null
     */
    public function getRequestHost(): string|null;

    /**
     * @return string|null
     */
    public function getRequestMethod(): string|null;

    /**
     * @return string|null
     */
    public function getRequestPath(): string|null;

    /**
     * @return RouteInterface|null
     */
    public function getCurrentRoute(): RouteInterface|null;

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * Process routing
     * @return MiddlewareInterface[]
     */
    public function process(): array;

    /**
     * @param Route $route 
     * @return self
     */
    public function addRoute(RouteInterface $route): self;

    /**
     * @param Route[] $route 
     * @return self
     */
    public function addRoutes(array $routes): self;

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
