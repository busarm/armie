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
    public function handle(App $app, callable $next = null): mixed
    {
        $result = $this->preflight($app);
        return $next ? $next() : $result;
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

            $max_cors_age = $app->config->httpCorsMaxAge ?? 3600;
            $allowed_cors_headers = $app->config->httpAllowedCorsHeaders ?? ['*'];
            $exposed_cors_headers = $app->config->httpExposedCorsHeaders ?? ['*'];
            $allowed_cors_methods = $app->config->httpAllowedCorsMethods ?? [
                HttpMethod::GET,
                HttpMethod::POST,
                HttpMethod::PUT,
                HttpMethod::PATCH,
                HttpMethod::OPTIONS,
                HttpMethod::DELETE
            ];

            // Convert the config items into strings
            $allowed_headers = implode(', ', is_array($allowed_cors_headers) ? $allowed_cors_headers : []);
            $exposed_cors_headers = implode(', ', is_array($exposed_cors_headers) ? $exposed_cors_headers : []);
            $allowed_methods = implode(', ', is_array($allowed_cors_methods) ? $allowed_cors_methods : []);

            // If we want to allow any domain to access the API
            if ($app->config->httpAllowAnyCorsDomain == TRUE) {
                $app->response->setHttpHeader('Access-Control-Allow-Origin', '*');
            } else {
                // We're going to allow only certain domains access
                // Store the HTTP Origin header
                $origin = $app->request->server('HTTP_ORIGIN') ?? $app->request->server('HTTP_REFERER') ?? '';
                $allowed_origins = $app->config->httpAllowedCorsOrigins ?? [];
                // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
                if (is_array($allowed_origins) && in_array(trim($origin, "/"), $allowed_origins)) {
                    $app->response->setHttpHeader('Access-Control-Allow-Origin', $origin);
                }
            }

            $app->response->setHttpHeader('Access-Control-Allow-Methods', $allowed_methods);
            $app->response->setHttpHeader('Access-Control-Allow-Headers', $allowed_headers);
            $app->response->setHttpHeader('Access-Control-Expose-Headers', $exposed_cors_headers);
            $app->response->setHttpHeader('Access-Control-Allow-Max-Age', $max_cors_age);

            // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
            if (strtoupper($app->request->method()) === HttpMethod::OPTIONS) {
                $app->response->setHttpHeader('Cache-Control', "max-age=$max_cors_age");
                return $app->response->html("Preflight Ok", 200);
            }
        } else {
            if (strtoupper($app->request->method()) === HttpMethod::OPTIONS) {
                // kill the response and send it to the client
                return $app->sendHttpResponse(200, "Preflight Ok");
            }
        }

        return true;
    }
}
