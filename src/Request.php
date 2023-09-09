<?php

namespace Armie;

use Armie\Bags\Bag;
use Armie\Bags\Cookie;
use Armie\Bags\Query;
use Armie\Bags\Session;
use Armie\Bags\StatelessCookie;
use Armie\Bags\StatelessSession;
use Armie\Bags\Upload;
use Armie\Enums\HttpMethod;
use Armie\Errors\SystemError;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\Resolver\AuthResolver;
use Armie\Interfaces\Resolver\HttpConnectionResolver;
use Armie\Interfaces\SessionStoreInterface;
use Armie\Interfaces\StorageBagInterface;
use Armie\Interfaces\UploadBagInterface;
use Armie\Resolvers\HttpConnection;
use Armie\Traits\Container;
use LogicException;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Workerman\Protocols\Http\Request as HttpRequest;

use const Armie\Constants\VAR_HTTP_HOST;
use const Armie\Constants\VAR_ORIG_PATH_INFO;
use const Armie\Constants\VAR_PATH_INFO;
use const Armie\Constants\VAR_REQUEST_URI;

/**
 * HTTP Request Provider.
 *
 * Armie Framework
 *
 * This class borrows heavily from the Symfony2 Framework and is part of the Symfony package.
 * See Symfony\Component\HttpFoundation\Request (https://github.com/symfony/symfony)
 *
 * @see Armie\Interface\RequestInterface
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Request implements RequestInterface
{
    use Container;

    protected string|null $_correlationId = null;
    protected string|null $_requestId = null;
    protected string|null $_ip = null;
    protected string|null $_scheme = null;
    protected string|null $_domain = null;
    protected string|null $_host = null;
    protected string|null $_baseUrl = null;
    protected string|null $_path = null;
    protected string|null $_currentUrl = null;
    protected HttpMethod|null $_method = null;
    protected string|null $_contentType = null;
    protected string|null $_protocol = null;
    protected mixed $_content = null;
    protected HttpRequest|null $_workerman = null;
    protected ServerRequestInterface|null $_psr = null;
    protected SessionStoreInterface|null $_session = null;
    protected StorageBagInterface|null $_cookies = null;
    protected StorageBagInterface|null $_request = null;
    protected StorageBagInterface|null $_query = null;
    protected StorageBagInterface|null $_server = null;
    protected StorageBagInterface|null $_headers = null;
    protected UploadBagInterface|StorageBagInterface|null $_files = null;
    protected AuthResolver|null $_auth = null;
    protected HttpConnectionResolver|null $_connection = null;

    /**
     * [RESTRICTED].
     */
    public function __serialize()
    {
        throw new SystemError('Serializing request instance is forbidden');
    }

    /**
     * Capture server request or create using Globals.
     *
     * @param ServerRequestInterface|null $psr
     * @param Config|null                 $config
     *
     * @return self
     */
    public static function capture(ServerRequestInterface|null $psr = null, Config|null $config = null): self
    {
        if (isset($psr)) {
            return self::fromPsr($psr, $config);
        } else {
            return self::fromGlobal($config);
        }
    }

    /**
     * Create request object using custom URL.
     *
     * @param string      $url
     * @param HttpMethod  $method
     * @param Config|null $config
     *
     * @return self
     */
    public static function fromUrl(string $url, HttpMethod $method = HttpMethod::GET, Config|null $config = null): self
    {
        if ($validUrl = filter_var($url, FILTER_VALIDATE_URL)) {
            $uri = new Uri($validUrl);
            $request = new self();
            $request->initialize(
                (new Query())->setQuery($uri->getQuery()),
                new Bag(),
                new Cookie(
                    $config ? $config->getCookieConfigs() : [],
                    $config?->cookiePrefix ?? str_replace(' ', '_', strtolower($config?->name)),
                    $config?->cookieEncrypt ? $config?->secret : null
                ),
                $config?->sessionEnabled ? (new Session(
                    $config ? $config->getSessionConfigs() : [],
                    $config?->cookieEncrypt ? $config?->secret : null
                )) : null,
                new Bag(),
                new Bag()
            );

            $request->_method = $method;
            $request->_scheme = $uri->getScheme();
            $request->_domain = $uri->getPort() ? $uri->getHost() . ':' . $uri->getPort() : $uri->getHost();
            $request->_host = $request->_scheme . '://' . $request->_domain;
            $request->_path = '/' . ltrim($uri->getPath(), '/');
            $request->_baseUrl = $request->_host;
            $request->_currentUrl = $request->_baseUrl . $request->_path;
            $request->_psr = null;

            return $request;
        } else {
            throw new SystemError("'$url' is not a valid URL");
        }
    }

    /**
     * Create request object from Globals.
     *
     * @param Config|null $config
     *
     * @return self
     */
    public static function fromGlobal(Config|null $config = null): self
    {
        $request = new self();
        $request->initialize(
            query: new Query($_GET),
            request: new Bag($_POST),
            cookies: new Cookie(
                $config ? $config->getCookieConfigs() : [],
                $config?->cookiePrefix ?? str_replace(' ', '_', strtolower($config?->name)),
                $config?->cookieEncrypt ? $config?->secret : null
            ),
            session: $config?->sessionEnabled ? (new Session(
                $config ? $config->getSessionConfigs() : [],
                $config?->cookieEncrypt ? $config?->secret : null
            )) : null,
            files: new Bag($_FILES),
            server: new Bag($_SERVER)
        );
        $request->_psr = null;

        return $request;
    }

    /**
     * Create request object from PSR7 Server request.
     *
     * @param ServerRequestInterface $psr
     * @param Config|null            $config
     *
     * @return self
     */
    public static function fromPsr(ServerRequestInterface $psr, Config|null $config = null): self
    {
        $request = new self();
        $request->initialize(
            query: (new Query($psr->getQueryParams()))->setQuery($psr->getUri()->getQuery()),
            request: new Bag((array) ($psr->getParsedBody() ?? [])),
            cookies: (new Cookie(
                $config ? $config->getCookieConfigs() : [],
                $config?->cookiePrefix ?? str_replace(' ', '_', strtolower($config?->name || '')),
                $config?->cookieEncrypt ? $config?->secret : null
            ))->load($psr->getCookieParams() ?? []),
            session: $config?->sessionEnabled ? (new Session(
                $config ? $config->getSessionConfigs() : [],
                $config?->cookieEncrypt ? $config?->secret : null
            )) : null,
            files: new Upload($psr->getUploadedFiles()),
            server: new Bag($psr->getServerParams()),
            headers: new Bag(array_map(fn ($header) => $header[0] ?? null, $psr->getHeaders()))
        );

        $request->_scheme = $psr->getUri()->getScheme();
        $request->_domain = $psr->getUri()->getPort() ? $psr->getUri()->getHost() . ':' . $psr->getUri()->getPort() : $psr->getUri()->getHost();
        $request->_host = $request->_scheme . '://' . $request->_domain;
        $request->_path = '/' . ltrim($psr->getUri()->getPath(), '/');
        $request->_baseUrl = $request->_host;
        $request->_currentUrl = $request->_baseUrl . $request->_path;
        $request->_psr = $psr;

        return $request;
    }

    /**
     * Create request object from Workerman HTTP request.
     *
     * @param HttpRequest $http
     * @param Config|null $config
     *
     * @return self
     */
    public static function fromWorkerman(HttpRequest $http, Config|null $config = null): self
    {
        $request = new self();
        $request->setConnection(new HttpConnection($http->connection));
        $request->initialize(
            query: new Query($http->get() ?? []),
            request: new Bag($http->post() ?? []),
            cookies: (new StatelessCookie(
                $config ? $config->getCookieConfigs() : [],
                $config?->cookiePrefix ?? str_replace(' ', '_', strtolower($config?->name)),
                $config?->cookieEncrypt ? $config?->secret : null
            ))->load($http->cookie() ?? []),
            session: $config?->sessionEnabled ? (new StatelessSession(
                $config?->getSessionConfigs()['name'] ?? 'PHPSESS',
                $config?->cookieEncrypt ? $config?->secret : null
            )) : null,
            files: new Upload($http->file()),
            server: new Bag($_SERVER),
            headers: new Bag($http->header())
        );

        $request->_protocol = $http->protocolVersion();
        $request->_host = $http->host();
        $request->_path = $http->path();
        $request->_baseUrl = $request->_host;
        $request->_currentUrl = $request->_baseUrl . $request->_path;
        $request->_ip = $http->connection?->getRemoteIp() ?? $request->_ip;
        $request->_workerman = $http;

        return $request;
    }

    /**
     * Change request's url. Clone request with new url.
     *
     * @param UriInterface $uri
     *
     * @return self
     */
    public function withUri(UriInterface $uri): self
    {
        $request = clone $this;
        $request->_scheme = $uri->getScheme();
        $request->_domain = $uri->getPort() ? $uri->getHost() . ':' . $uri->getPort() : $uri->getHost();
        $request->_host = $request->_scheme . '://' . $request->_domain;
        $request->_path = '/' . ltrim($uri->getPath(), '/');
        $request->_baseUrl = $request->_host;
        $request->_currentUrl = $request->_baseUrl . $request->_path;
        $request->_psr = null;

        return $request;
    }

    /**
     * Sets the parameters for this request.
     *
     * This method also re-initializes all properties.
     *
     * @param StorageBagInterface                    $query   - The GET parameters
     * @param StorageBagInterface                    $request - The POST parameters
     * @param StorageBagInterface                    $cookies - The COOKIE parameters
     * @param SessionStoreInterface                  $session - The SESSION parameters
     * @param UploadBagInterface|StorageBagInterface $files   - The FILES parameters
     * @param StorageBagInterface                    $server  - The SERVER parameters
     * @param StorageBagInterface                    $headers - The headers
     * @param string                                 $content - The raw body data
     *
     * @return self
     */
    public function initialize(
        StorageBagInterface|null $query = null,
        StorageBagInterface|null $request = null,
        StorageBagInterface|null $cookies = null,
        SessionStoreInterface|null $session = null,
        UploadBagInterface|StorageBagInterface|null $files = null,
        StorageBagInterface|null $server = null,
        StorageBagInterface|null $headers = null,
        $content = null,
    ): self {
        $this->_request = $request ?: $this->_request;
        $this->_query = $query ?: $this->_query;
        $this->_session = $session ?: $this->_session;
        $this->_cookies = $cookies ?: $this->_cookies;
        $this->_files = $files ?: $this->_files;
        $this->_server = $server ?: $this->_server;
        $this->_content = $content ?: $this->_content;

        // Load data from server vars
        if ($this->_server) {
            $this->_headers = $headers ?: new Bag(array_merge(
                $this->getHeadersFromServer($this->_server->all()),
                $this->_headers ? $this->_headers->all() : []
            ));
            $this->_contentType = $this->_contentType ?: $this->_server->get('CONTENT_TYPE', '');
            $this->_method = $this->_method ?: ($this->_server->get('REQUEST_METHOD') ? HttpMethod::tryFrom($this->_server->get('REQUEST_METHOD')) : HttpMethod::GET);
            $this->_protocol = $this->_protocol ?: $this->getServerPotocol();

            // Load request body
            if (
                (!$this->_request || empty($this->_request->all())) &&
                0 === strpos($this->_contentType, 'application/x-www-form-urlencoded') &&
                in_array($this->_method, [HttpMethod::POST, HttpMethod::PUT, HttpMethod::DELETE])
            ) {
                parse_str($this->getContent(), $data);
                $this->_request = new Bag($data);
            } elseif (
                (!$this->_request || empty($this->_request->all())) &&
                0 === strpos($this->_contentType, 'application/json') &&
                in_array($this->_method, [HttpMethod::POST, HttpMethod::PUT, HttpMethod::DELETE])
            ) {
                $data = json_decode($this->getContent(), true);
                $this->_request = new Bag($data);
            }

            $this->_scheme = $this->_scheme ?: ($this->isHttps() ? 'https' : 'http');
            $this->_ip = $this->_ip ?: $this->getIpAddress();
            $this->_domain = $this->_domain ?: $this->_server->get(VAR_HTTP_HOST);
            $this->_host = $this->_host ?: $this->_scheme . '://' . $this->_domain;
            if (!$this->_path) {
                $this->_path = '/' . ltrim($this->_server->get(VAR_REQUEST_URI) ?: ($this->_server->get(VAR_PATH_INFO) ?: $this->_server->get(VAR_ORIG_PATH_INFO) ?: ''), '/');
                $this->_path = rawurldecode(explode('?', $this->_path ?? '', 2)[0]);
            }
            $this->_baseUrl = $this->_baseUrl ?: $this->_host;
            $this->_currentUrl = $this->_currentUrl ?: $this->_baseUrl . $this->_path;

            $this->_correlationId = ($this->_headers->get('x-trace-id') ??
                $this->_headers->get('x-correlation-id'))
                ?: sha1(uniqid($this->ip() ?? ''));

            $this->_requestId = ($this->_headers->get('x-request-id') ??
                $this->_headers->get('request-id'))
                ?: floor(microtime(true) * 1000) . '.' . bin2hex(random_bytes(16));
        }

        return $this;
    }

    /**
     * Returns the request body content.
     *
     * @param bool $asResource - If true, a resource will be returned
     *
     * @throws LogicException
     *
     * @return string|resource - The request body content or a resource to read the body stream.
     */
    public function getContent($asResource = false)
    {
        if (false === $this->_content || (true === $asResource && null !== $this->_content)) {
            throw new LogicException('getContent() can only be called once when using the resource return type.');
        }

        if (true === $asResource) {
            $this->_content = false;

            return fopen('php://input', 'rb');
        }

        if (null === $this->_content) {
            $this->_content = file_get_contents('php://input');
        }

        return $this->_content;
    }

    /**
     * Get server protocol or http version.
     *
     * @return string
     */
    private function getServerPotocol()
    {
        return (!empty($this->_server->get('SERVER_PROTOCOL')) && in_array($this->_server->get('SERVER_PROTOCOL'), ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'], true))
            ? $this->_server->get('SERVER_PROTOCOL') : 'HTTP/1.1';
    }

    /**
     * Check if https enabled.
     *
     * @return bool
     */
    protected function isHttps()
    {
        if (!empty($this->_server->get('HTTPS')) && strtolower($this->_server->get('HTTPS')) !== 'off') {
            return true;
        } elseif (!empty($this->_server->get('HTTP_X_FORWARDED_PROTO')) && strtolower($this->_server->get('HTTP_X_FORWARDED_PROTO')) === 'https') {
            return true;
        } elseif (!empty($this->_server->get('HTTP_FRONT_END_HTTPS')) && strtolower($this->_server->get('HTTP_FRONT_END_HTTPS')) !== 'off') {
            return true;
        }

        return false;
    }

    /**
     * Get Ip.
     *
     * @return string
     */
    protected function getIpAddress()
    {
        // check for IPs passing through proxies
        if (!empty($this->_server->get('HTTP_X_FORWARDED_FOR'))) {
            // check if multiple ips exist in var
            if (strpos($this->_server->get('HTTP_X_FORWARDED_FOR'), ',') !== false) {
                $iplist = explode(',', $this->_server->get('HTTP_X_FORWARDED_FOR') ?? '', 20);
                foreach ($iplist as $ip) {
                    if ($this->validateIpAddress($ip)) {
                        return $ip;
                    }
                }
            } else {
                if ($this->validateIpAddress($this->_server->get('HTTP_X_FORWARDED_FOR'))) {
                    return $this->_server->get('HTTP_X_FORWARDED_FOR');
                }
            }
        }
        // check for shared internet/ISP IP
        if (!empty($this->_server->get('HTTP_CLIENT_IP')) && $this->validateIpAddress($this->_server->get('HTTP_CLIENT_IP'))) {
            return $this->_server->get('HTTP_CLIENT_IP');
        }
        if (!empty($this->_server->get('HTTP_X_FORWARDED')) && $this->validateIpAddress($this->_server->get('HTTP_X_FORWARDED'))) {
            return $this->_server->get('HTTP_X_FORWARDED');
        }

        if (!empty($this->_server->get('HTTP_X_CLUSTER_CLIENT_IP')) && $this->validateIpAddress($this->_server->get('HTTP_X_CLUSTER_CLIENT_IP'))) {
            return $this->_server->get('HTTP_X_CLUSTER_CLIENT_IP');
        }

        if (!empty($this->_server->get('HTTP_FORWARDED_FOR')) && $this->validateIpAddress($this->_server->get('HTTP_FORWARDED_FOR'))) {
            return $this->_server->get('HTTP_FORWARDED_FOR');
        }

        if (!empty($this->_server->get('HTTP_FORWARDED')) && $this->validateIpAddress($this->_server->get('HTTP_FORWARDED'))) {
            return $this->_server->get('HTTP_FORWARDED');
        }

        // return unreliable ip since all else failed
        return $this->_server->get('REMOTE_ADDR');
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     *
     * @param string|null        $ip
     * @param 'ipv4'|'ipv6'|null $type
     *
     * @return bool
     */
    protected function validateIpAddress(string|null $ip, string|null $type = null)
    {
        if (!$ip || strtolower($ip) === 'unknown') {
            return false;
        }

        $isValid = false;

        if ($type == 'ipv4') {
            // Validates IPV4
            $isValid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        } elseif ($type == 'ipv6') {
            // Validates IPV6
            $isValid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        } else {
            // Validates IPV4 and IPV6
            $isValid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        if ($isValid == $ip) {
            $isValid = true;
        }

        return $isValid;
    }

    /**
     * Get request headers from Server Variables.
     *
     * @param array $server
     *
     * @return array
     */
    protected function getHeadersFromServer(array $server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (in_array($key, ['CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'])) {
                $headers[$key] = $value;
            }
        }

        if (isset($server['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $server['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($server['PHP_AUTH_PW']) ? $server['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add this line to your .htaccess file:
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */

            $authorizationHeader = null;
            if (isset($server['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $server['HTTP_AUTHORIZATION'];
            } elseif (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $server['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (function_exists('apache_request_headers')) {
                $requestHeaders = (array) apache_request_headers();

                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

                if (isset($requestHeaders['Authorization'])) {
                    $authorizationHeader = trim($requestHeaders['Authorization'] ?: '');
                }
            }

            if (null !== $authorizationHeader) {
                $headers['AUTHORIZATION'] = $authorizationHeader;
                // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                if (0 === stripos($authorizationHeader, 'basic')) {
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6) ?? '', 3));
                    if (count($exploded) == 2) {
                        list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                    }
                }
            }
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        }

        return $headers;
    }

    /**
     * @return string
     */
    public function correlationId()
    {
        return $this->_correlationId;
    }

    /**
     * @return string
     */
    public function requestId()
    {
        return $this->_requestId;
    }

    /**
     * @return string
     */
    public function ip()
    {
        return $this->_ip;
    }

    /**
     * @return string
     */
    public function scheme()
    {
        return $this->_scheme;
    }

    /**
     * @return string
     */
    public function domain()
    {
        return $this->_domain;
    }

    /**
     * @return string
     */
    public function host()
    {
        return $this->_host;
    }

    /**
     * @return string
     */
    public function baseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->_path;
    }

    /**
     * @return string
     */
    public function currentUrl()
    {
        return $this->_currentUrl;
    }

    /**
     * @return HttpMethod
     */
    public function method(): HttpMethod
    {
        return $this->_method;
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return $this->_contentType;
    }

    /**
     * @return mixed
     */
    public function content()
    {
        return $this->_content;
    }

    /**
     * Get all of the segments for the request path.
     *
     * @return array
     */
    public function segments()
    {
        $segments = explode('/', $this->path() ?? '', 100);

        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }

    /**
     * @return StorageBagInterface
     */
    public function file(): UploadBagInterface|StorageBagInterface
    {
        return $this->_files;
    }

    /**
     * @return ?SessionStoreInterface
     */
    public function session(): ?SessionStoreInterface
    {
        return $this->_session;
    }

    /**
     * @return StorageBagInterface
     */
    public function cookie(): StorageBagInterface
    {
        return $this->_cookies;
    }

    /**
     * @return StorageBagInterface
     */
    public function query(): StorageBagInterface
    {
        return $this->_query;
    }

    /**
     * @return StorageBagInterface
     */
    public function request(): StorageBagInterface
    {
        return $this->_request;
    }

    /**
     * @return AuthResolver|null
     */
    public function auth(): AuthResolver|null
    {
        return $this->_auth;
    }

    /**
     * @return HttpConnectionResolver|null
     */
    public function connection(): HttpConnectionResolver|null
    {
        return $this->_connection;
    }

    /**
     * @return StorageBagInterface
     */
    public function server(): StorageBagInterface
    {
        return $this->_server;
    }

    /**
     * @return StorageBagInterface
     */
    public function header(): StorageBagInterface
    {
        return $this->_headers;
    }

    /**
     * @return string
     */
    public function version()
    {
        return $this->_protocol ? str_replace('HTTP/', '', $this->_protocol) : '1.1';
    }

    /**
     * Set the value of session.
     *
     * @return self
     */
    public function setSession(SessionStoreInterface $session): self
    {
        $this->_session = $session;

        return $this;
    }

    /**
     * Set the value of request.
     *
     * @return self
     */
    public function setRequest(StorageBagInterface $request): self
    {
        $this->_request = $request;

        return $this;
    }

    /**
     * Set the value of query.
     *
     * @return self
     */
    public function setQuery(StorageBagInterface $query): self
    {
        $this->_query = $query;

        return $this;
    }

    /**
     * Set the value of server.
     *
     * @return self
     */
    public function setServer(StorageBagInterface $server): self
    {
        $this->_server = $server;

        return $this;
    }

    /**
     * Set the value of files.
     *
     * @return self
     */
    public function setFiles(UploadBagInterface|StorageBagInterface $files): self
    {
        $this->_files = $files;

        return $this;
    }

    /**
     * Set the value of cookies.
     *
     * @return self
     */
    public function setCookies(StorageBagInterface $cookies): self
    {
        $this->_cookies = $cookies;

        return $this;
    }

    /**
     * Set the value of headers.
     *
     * @return self
     */
    public function setHeaders(StorageBagInterface $headers): self
    {
        $this->_headers = $headers;

        return $this;
    }

    /**
     * Set the value of auth.
     *
     * @return self
     */
    public function setAuth(AuthResolver $auth): self
    {
        $this->_auth = $auth;

        return $this;
    }

    /**
     * Set the value of auth.
     *
     * @return self
     */
    public function setConnection(HttpConnectionResolver $connection): self
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * Get PSR7 Server request.
     *
     * @return ServerRequestInterface
     */
    public function toPsr(): ServerRequestInterface
    {
        $request = $this->_psr ?? (new \Nyholm\Psr7\ServerRequest(
            $this->_method->value,
            (new Uri($this->_currentUrl))->withQuery(strval($this->_query)),
            $this->_headers ? $this->_headers->all() : [],
            $this->_content ?: strval($this->_request),
            $this->version(),
            $this->_server ? $this->_server->all() : []
        ))
            ->withProtocolVersion($this->version())
            ->withUploadedFiles($this->file()->all())
            ->withCookieParams($this->cookie()->all());

        return $request;
    }
}
