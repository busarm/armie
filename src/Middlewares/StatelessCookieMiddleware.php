<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Config;
use Busarm\PhpMini\Dto\CookieDto;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

use function Busarm\PhpMini\Helpers\create_cookie_header;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class StatelessCookieMiddleware implements MiddlewareInterface
{
    public function __construct(private Config|null $config = null)
    {
    }

    /**
     * Middleware handler
     *
     * @param RequestInterface|RouteInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(RequestInterface|RouteInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request instanceof RequestInterface) {
            $response = $handler->handle($request);
            if (!empty($cookies = $request->cookie()->updates())) {
                $headers = [];
                foreach ($cookies as $name => $cookie) {
                    if ($cookie instanceof CookieDto) {
                        $headers[$name] = create_cookie_header(
                            $name,
                            $cookie->value,
                            $cookie->expires,
                            $cookie->path,
                            $cookie->domain,
                            $cookie->samesite,
                            boolval($cookie->secure),
                            boolval($cookie->httponly),
                        );
                    } else {
                        $options = $this->config?->getCookieConfigs() ?? [];
                        $headers[$name] = create_cookie_header(
                            $name,
                            $cookie,
                            $options['expires'] ?? 0,
                            $options['path'] ?? '',
                            $options['domain'] ?? '',
                            $options['samesite'] ?? 0,
                            boolval($options['secure'] ?? true),
                            boolval($options['httponly'] ?? true),
                        );
                    }
                }

                // Add Cookie headers
                $response->setHttpHeader('Set-Cookie', $headers);
            }

            // Clear current cookie list from memory
            $request->cookie()->clear();
            return $response;
        }
        return $handler->handle($request);
    }
}
