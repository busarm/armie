<?php

namespace Busarm\PhpMini\Handlers;

use Busarm\PhpMini\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class ServerRequestHandler implements RequestHandlerInterface
{
    public function __construct(private RequestHandler $next)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->next->handle(Request::fromPsr($request))->toPsr();
    }
}
