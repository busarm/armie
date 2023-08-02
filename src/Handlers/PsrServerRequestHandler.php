<?php

namespace Busarm\PhpMini\Handlers;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrServerRequestHandlerInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
