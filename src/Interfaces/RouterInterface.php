<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\MiddlewareInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface RouterInterface
{
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
}
