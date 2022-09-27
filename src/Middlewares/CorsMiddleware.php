<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

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

    public function handle(RequestInterface|RouteInterface &$request, ResponseInterface &$response, callable $next = null): mixed
    {
        // Only allowed for HTTP requests
        if ($request instanceof RequestInterface) {
            $result = $this->preflight($request, $response);
            if ($result === false) {
                return $next ? $next() : true;
            }
            return $result;
        }
        return $next ? $next() : true;
    }

    /**
     * Preflight Check
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
     */
    private function preflight(RequestInterface &$request, ResponseInterface &$response): mixed
    {
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
                $response->setHttpHeader('Access-Control-Allow-Origin', $origin);
            }
            // Allow only certain domains access
            else {
                // If the origin domain is in the allowed cors origins list, then add the Access Control header
                if (is_array($this->allowedOrigins) && in_array($origin, $this->allowedOrigins)) {
                    $response->setHttpHeader('Access-Control-Allow-Origin', $origin);
                } else return $response->html("Unauthorized", 401, false);
            }

            $response->setHttpHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->setHttpHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $response->setHttpHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
            $response->setHttpHeader('Access-Control-Allow-Max-Age', $this->maxAge);

            // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
            if (strtoupper($request->method()) === HttpMethod::OPTIONS) {
                $response->setHttpHeader('Cache-Control', "max-age=" . $this->maxAge);
                return $response->html("Preflight Ok", 200, false);
            }
        }
        // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
        else if (strtoupper($request->method()) === HttpMethod::OPTIONS) {
            return $response->html("Preflight Ok", 200, false);
        }

        return false;
    }
}
