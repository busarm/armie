<?php

namespace Busarm\PhpMini\Traits;

use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

use function Busarm\PhpMini\Helpers\app;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 29/7/2022
 * Time: 11:21 AM
 */
trait SingletonStateless
{
    /**
     * Create / Retrieve stateless singleton instance
     *
     * @param RequestInterface|RouteInterface $request
     * @param array $params
     * @return static
     */
    public static function make(RequestInterface|RouteInterface $request, ResponseInterface $response, array $params = []): static
    {
        return app()->make(static::class, $params, $request, $response);
    }
}
