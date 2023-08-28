<?php

namespace Armie\Handlers;

use Armie\Config;
use Armie\Interfaces\RequestHandlerInterface;
use Armie\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrServerRequestHandlerInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class PsrServerRequestHandler implements PsrServerRequestHandlerInterface
{
    public function __construct(private RequestHandlerInterface $next, private Config|null $config = null)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->next->handle(Request::fromPsr($request, $this->config))->toPsr();
    }
}
