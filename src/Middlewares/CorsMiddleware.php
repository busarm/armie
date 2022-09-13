<?php

namespace Busarm\PhpMini\Middlewares;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;

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

    public function handle(App $app, callable $next = null): mixed
    {
        $result = $this->preflight($app);
        if ($result === false) {
            return $next ? $next() : true;
        }
        return $result;
    }

    /**
     * Preflight Check
     *
     * @return mixed
     */
    private function preflight(App $app)
    {
        // Check for CORS access request
        if ($app->config->httpCheckCors == TRUE) {

            $max_cors_age           =   !empty($this->maxAge) ? $this->maxAge : $app->config->httpCorsMaxAge;
            $allowed_origins        =   !empty($this->allowedOrigins) ? $this->allowedOrigins : $app->config->httpAllowedCorsOrigins;
            $allowed_cors_headers   =   !empty($this->allowedHeaders) ? $this->allowedHeaders : $app->config->httpAllowedCorsHeaders;
            $exposed_cors_headers   =   !empty($this->exposedHeaders) ? $this->exposedHeaders : $app->config->httpExposedCorsHeaders;
            $allowed_cors_methods   =   !empty($this->allowedMethods) ? $this->allowedMethods : $app->config->httpAllowedCorsMethods;

            // If we want to allow any domain to access the API
            if ($app->config->httpAllowAnyCorsDomain == TRUE) {
                $app->response->setHttpHeader('Access-Control-Allow-Origin', '*');
            } else {
                // We're going to allow only certain domains access
                // Store the HTTP Origin header
                $origin = $app->request->server('HTTP_ORIGIN') ?? $app->request->server('HTTP_REFERER') ?? '';
                // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
                if (is_array($allowed_origins) && in_array(trim($origin, "/"), $allowed_origins)) {
                    $app->response->setHttpHeader('Access-Control-Allow-Origin', $origin);
                }
            }

            $app->response->setHttpHeader('Access-Control-Allow-Methods', implode(', ', is_array($allowed_cors_methods) ? $allowed_cors_methods : []));
            $app->response->setHttpHeader('Access-Control-Allow-Headers', implode(', ', is_array($allowed_cors_headers) ? $allowed_cors_headers : []));
            $app->response->setHttpHeader('Access-Control-Expose-Headers', implode(', ', is_array($exposed_cors_headers) ? $exposed_cors_headers : []));
            $app->response->setHttpHeader('Access-Control-Allow-Max-Age', $max_cors_age);

            // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
            if (strtoupper($app->request->method()) === HttpMethod::OPTIONS) {
                $app->response->setHttpHeader('Cache-Control', "max-age=$max_cors_age");
                return $app->response->html("Preflight Ok", 200, false);
            }
        } else {
            if (strtoupper($app->request->method()) === HttpMethod::OPTIONS) {
                // kill the response and send it to the client
                return $app->response->html("Preflight Ok", 200, false);
            }
        }

        return false;
    }
}
