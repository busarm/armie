<?php

namespace Armie\Interfaces;

use Armie\Interfaces\Data\ResourceControllerInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface HttpServerInterface
{
    /**
     * Set HTTP GET routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public function get(string $path): RouteInterface;

    /**
     * Set HTTP POST routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public function post(string $path): RouteInterface;

    /**
     * Set HTTP PUT routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public function put(string $path): RouteInterface;

    /**
     * Set HTTP PATCH routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public function patch(string $path): RouteInterface;

    /**
     * Set HTTP DELETE routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public function delete(string $path): RouteInterface;

    /**
     * Set HTTP HEAD routes
     * 
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public function head(string $path): RouteInterface;

    /**
     * Set HTTP Resource (CREATE/READ/UPDATE/DELETE) routes for controller
     * Creates the following routes:
     * - GET    $path/list      ->  ResourceControllerInterface::list
     * - GET    $path/paginate  ->  ResourceControllerInterface::paginatedList
     * - GET    $path/{id}      ->  ResourceControllerInterface::get
     * - POST   $path/bulk      ->  ResourceControllerInterface::createBulk
     * - POST   $path           ->  ResourceControllerInterface::create
     * - PUT    $path/bulk      ->  ResourceControllerInterface::updateBulk
     * - PUT    $path/{id}      ->  ResourceControllerInterface::update
     * - DELETE $path/bulk      ->  ResourceControllerInterface::deleteBulk
     * - DELETE $path/{id}      ->  ResourceControllerInterface::delete
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @param class-string<ResourceControllerInterface> $controller Application Controller class name e.g Home
     * @return mixed
     */
    public function resource(string $path, string $controller);
}
