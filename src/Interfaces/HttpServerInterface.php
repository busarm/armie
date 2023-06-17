<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\Data\CrudControllerInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
     * Set HTTP CRUD (CREATE/READ/UPDATE/DELETE) routes for controller
     * Creates the following routes:
     * - GET    $path/list      ->  CrudControllerInterface::list
     * - GET    $path/paginate  ->  CrudControllerInterface::paginatedList
     * - GET    $path/{id}      ->  CrudControllerInterface::get
     * - POST   $path/bulk      ->  CrudControllerInterface::createBulk
     * - POST   $path           ->  CrudControllerInterface::create
     * - PUT    $path/bulk      ->  CrudControllerInterface::updateBulk
     * - PUT    $path/{id}      ->  CrudControllerInterface::update
     * - DELETE $path/bulk      ->  CrudControllerInterface::deleteBulk
     * - DELETE $path/{id}      ->  CrudControllerInterface::delete
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @param class-string<CrudControllerInterface> $controller Application Controller class name e.g Home
     * @return mixed
     */
    public function crud(string $path, string $controller);
}
