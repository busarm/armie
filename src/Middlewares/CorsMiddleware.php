<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Response;

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private array $allowedOrigins = [],
        private array $allowedHeaders = [],
        private array $exposedHeaders = [],
        private array $allowedMethods = [],
        private int $maxAge = 0

    ) {
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
        if (strtoupper($request->method()) === HttpMethod::OPTIONS) {
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
        if (app()->config->httpCheckCors == TRUE) {

            $this->maxAge           =   !empty($this->maxAge) ? $this->maxAge : app()->config->httpCorsMaxAge;
            $this->allowedOrigins   =   app()->config->httpAllowAnyCorsDomain ? ['*'] : (!empty($this->allowedOrigins) ? $this->allowedOrigins : app()->config->httpAllowedCorsOrigins);
            $this->allowedHeaders   =   !empty($this->allowedHeaders) ? $this->allowedHeaders : app()->config->httpAllowedCorsHeaders;
            $this->exposedHeaders   =   !empty($this->exposedHeaders) ? $this->exposedHeaders : app()->config->httpExposedCorsHeaders;
            $this->allowedMethods   =   !empty($this->allowedMethods) ? $this->allowedMethods : app()->config->httpAllowedCorsMethods;

            $origin = trim($request->server()->get('HTTP_ORIGIN') ?: $request->server()->get('HTTP_REFERER') ?: '', "/");

            // Allow any domain access
            if (in_array('*', $this->allowedOrigins)) {
                $response->setHttpHeader('Access-Control-Allow-Origin', $origin ?: '*');
            }
            // Allow only certain domains access
            // If the origin domain is in the allowed cors origins list, then add the Access Control header
            elseif (is_array($this->allowedOrigins) && in_array($origin, $this->allowedOrigins)) {
                $response->setHttpHeader('Access-Control-Allow-Origin', $origin);
            }
            // Reject request if not from same origin host
            elseif ($origin != $request->domain()) {
                $response->html("Unauthorized", 401);
                return $response;
            }

            $response->setHttpHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->setHttpHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $response->setHttpHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
            $response->setHttpHeader('Access-Control-Allow-Max-Age', $this->maxAge);

            if (strtoupper($request->method()) === HttpMethod::OPTIONS) {
                $response->setHttpHeader('Cache-Control', "max-age=" . $this->maxAge);
                $response->html("Preflight Ok");
            }
        } elseif (strtoupper($request->method()) === HttpMethod::OPTIONS) {
            $response->html("Preflight Ok");
        }

        return $response;
    }
}
