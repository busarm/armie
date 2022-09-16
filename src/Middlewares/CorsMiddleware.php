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

            $this->maxAge           =   !empty($this->maxAge) ? $this->maxAge : $app->config->httpCorsMaxAge;
            $this->allowedOrigins   =   $app->config->httpAllowAnyCorsDomain ? ['*'] : (!empty($this->allowedOrigins) ? $this->allowedOrigins : $app->config->httpAllowedCorsOrigins);
            $this->allowedHeaders   =   !empty($this->allowedHeaders) ? $this->allowedHeaders : $app->config->httpAllowedCorsHeaders;
            $this->exposedHeaders   =   !empty($this->exposedHeaders) ? $this->exposedHeaders : $app->config->httpExposedCorsHeaders;
            $this->allowedMethods   =   !empty($this->allowedMethods) ? $this->allowedMethods : $app->config->httpAllowedCorsMethods;

            $origin = trim($app->request->server('HTTP_ORIGIN') ?: $app->request->server('HTTP_REFERER') ?: '', "/");

            // Allow any domain access
            if (in_array('*', $this->allowedOrigins)) {
                $app->response->setHttpHeader('Access-Control-Allow-Origin', $origin);
            }
            // Allow only certain domains access
            else {
                // If the origin domain is in the allowed cors origins list, then add the Access Control header
                if (is_array($this->allowedOrigins) && in_array($origin, $this->allowedOrigins)) {
                    $app->response->setHttpHeader('Access-Control-Allow-Origin', $origin);
                } else return $app->response->html("Unauthorized", 401, false);
            }

            $app->response->setHttpHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $app->response->setHttpHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $app->response->setHttpHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
            $app->response->setHttpHeader('Access-Control-Allow-Max-Age', $this->maxAge);

            // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
            if (strtoupper($app->request->method()) === HttpMethod::OPTIONS) {
                $app->response->setHttpHeader('Cache-Control', "max-age=" . $this->maxAge);
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
