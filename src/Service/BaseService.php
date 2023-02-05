<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ServiceProviderInterface;
use Busarm\PhpMini\Interfaces\SingletonStatelessInterface;
use Busarm\PhpMini\Server;
use Busarm\PhpMini\Traits\SingletonStateless;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class BaseService implements ServiceProviderInterface, SingletonStatelessInterface
{
    use SingletonStateless;

    public function __construct(private RequestInterface $request)
    {
    }

    /**
     * Get service location for name
     * 
     * @param string $name
     * @return string
     */
    public function get($name)
    {
        return $this->request->server()->get(Server::HEADER_SERVICE_CLIENT_PREFIX . strtoupper($name));
    }

    /**
     * Get current service name
     * 
     * @return string
     */
    public function getCurrentServiceName()
    {
        return $this->request->server()->get(Server::HEADER_SERVICE_NAME);
    }
}
