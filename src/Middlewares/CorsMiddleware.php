<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Config;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Response;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private Config $config)
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
        // Only allowed for HTTP requests
        if ($request instanceof RequestInterface) {
            $result = $this->preflight($request);
            if ($result !== false) {
                return $result;
            }
            $response = $handler->handle($request);
            return $this->handle($request, $response);
        }
        return $handler->handle($request);
    }

    /**
     * Preflight Check
     *
     * @param RequestInterface $request
     * @return ResponseInterface|false
     */
    private function preflight(RequestInterface $request): ResponseInterface|bool
    {
        // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
        if ($request->method() === HttpMethod::OPTIONS) {
            return $this->handle($request);
        }
        return false;
    }

    /**
     * Handle CORS process
     *
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    private function handle(RequestInterface $request, ResponseInterface|null $response = null): ResponseInterface
    {
        $response = $response ?: new Response(version: $request->version());

        // Check for CORS access request
        if ($this->config->http->checkCors == TRUE) {

            $maxAge           =   $this->config->http->corsMaxAge;
            $allowedOrigins   =   $this->config->http->allowAnyCorsDomain ? ['*'] : $this->config->http->allowedCorsOrigins;
            $allowedHeaders   =   $this->config->http->allowedCorsHeaders;
            $exposedHeaders   =   $this->config->http->exposedCorsHeaders;
            $allowedMethods   =   $this->config->http->allowedCorsMethods;

            $origin = trim($request->server()->get('HTTP_ORIGIN') ?: $request->server()->get('HTTP_REFERER') ?: '', "/");

            // Allow any domain access
            if (in_array('*', $allowedOrigins)) {
                $response->setHttpHeader('Access-Control-Allow-Origin', $origin ?: '*');
            }
            // Allow only certain domains access
            // If the origin domain is in the allowed cors origins list, then add the Access Control header
            else if (is_array($allowedOrigins) && in_array($origin, $allowedOrigins)) {
                $response->setHttpHeader('Access-Control-Allow-Origin', $origin);
            }
            // Reject request if not from same origin host
            else if ($origin != $request->domain()) {
                $response->html("Unauthorized", 401);
                return $response;
            }

            $response->setHttpHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
            $response->setHttpHeader('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
            $response->setHttpHeader('Access-Control-Expose-Headers', implode(', ', $exposedHeaders));
            $response->setHttpHeader('Access-Control-Allow-Max-Age', $maxAge);

            if ($request->method() === HttpMethod::OPTIONS) {
                $response->setHttpHeader('Cache-Control', "max-age=" . $maxAge);
                $response->html("Preflight Ok");
            }
        } else if ($request->method() === HttpMethod::OPTIONS) {
            $response->html("Preflight Ok");
        }

        return $response;
    }
}
