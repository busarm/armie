<?php

namespace Busarm\PhpMini;

use LogicException;
use Nyholm\Psr7\Uri;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Bags\Attribute;
use Busarm\PhpMini\Bags\Cookie;
use Busarm\PhpMini\Bags\Query;
use Busarm\PhpMini\Bags\Upload;
use Busarm\PhpMini\Session\PHPSession;
use Busarm\PhpMini\Interfaces\StorageBagInterface;
use Busarm\PhpMini\Interfaces\SessionStoreInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\UploadBagInterface;
use Busarm\PhpMini\Traits\Container;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

use function Busarm\PhpMini\Helpers\app;
use function Busarm\PhpMini\Helpers\is_cli;

/**
 * HTTP Request Provider
 * 
 * PHP Mini Framework
 *
 * This class borrows heavily from the Symfony2 Framework and is part of the Symfony package.
 * See Symfony\Component\HttpFoundation\Request (https://github.com/symfony/symfony)
 * 
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Request implements RequestInterface
{
    use Container;

    protected string|null $ip           =   NULL;
    protected string|null $scheme       =   NULL;
    protected string|null $domain       =   NULL;
    protected string|null $host         =   NULL;
    protected string|null $baseUrl      =   NULL;
    protected string|null $uri          =   NULL;
    protected string|null $currentUrl   =   NULL;
    protected string|null $method       =   NULL;
    protected string|null $contentType  =   NULL;
    protected string|null $protocol     =   NULL;
    protected mixed $content  =   NUll;
    protected UploadBagInterface|StorageBagInterface|null $files      =   null;
    protected SessionStoreInterface|null $session   =   null;
    protected StorageBagInterface|null $request     =   null;
    protected StorageBagInterface|null $query       =   null;
    protected StorageBagInterface|null $server      =   null;
    protected StorageBagInterface|null $cookies     =   null;
    protected StorageBagInterface|null $headers     =   null;

    protected ServerRequestInterface|null $psr      =   NUll;

    private array $sessionOptions   =   [];
    private array $cookieOptions    =   [];

    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->sessionOptions = [
            'cookie_lifetime' => app()->config->cookieDuration,
            'cookie_path' => app()->config->cookiePath,
            'cookie_domain' => app()->config->cookieDomain,
            'cookie_secure' => app()->config->cookieSecure,
            'cookie_httponly' => app()->config->cookieHttpOnly,
            'cookie_samesite' => app()->config->cookieSameSite,
            'cache_expire' => app()->config->cookieDuration / 60,
            'cache_limiter' => app()->config->cacheLimiter,
            'save_path' => app()->config->sessionPath,
            'name' => str_replace(' ', '_', strtolower(app()->config->name)) . '_' . crc32(app()->config->sessionPath) . '_sess'
        ];

        $this->cookieOptions = [
            'expires' => time() + app()->config->cookieDuration,
            'path' => app()->config->cookiePath,
            'domain' => app()->config->cookieDomain,
            'secure' => app()->config->cookieSecure,
            'httponly' => app()->config->cookieHttpOnly,
            'samesite' => app()->config->cookieSameSite,
        ];
    }

    /**
     * Create request object using custom URL
     *
     * @param string $url
     * @param string $method
     * @return self
     */
    public static function fromUrl($url, $method = HttpMethod::GET): self
    {
        if ($validUrl = filter_var($url, FILTER_VALIDATE_URL)) {
            $uri = new Uri($validUrl);
            $request = new self();
            $request->initialize(
                (new Query)->setQuery($uri->getQuery()),
                (new Attribute),
                (new Cookie($request->cookieOptions, app()->config->cookieEncrypt, $request->ip() ?? '')),
                (new PHPSession($request->sessionOptions)),
                (new Attribute),
                (new Attribute)
            );

            $request->method = $method;
            $request->scheme = $uri->getScheme();
            $request->domain = $uri->getPort() ? $uri->getHost() . ':' . $uri->getPort() : $uri->getHost();
            $request->host = $request->scheme  . "://" . $request->domain;
            $request->uri = '/' . ltrim($uri->getPath(), '/');
            $request->baseUrl = $request->host;
            $request->currentUrl = $request->baseUrl . $request->uri;
            return $request;
        } else {
            throw new SystemError("'$url' is not a valid URL");
        }
    }

    /**
     * Create request object from Globals
     * 
     * @return self
     */
    public static function fromGlobal(): self
    {
        $request = new self;
        $request->initialize(
            (new Query)->mirror($_GET),
            (new Attribute)->mirror($_POST),
            (new Cookie($request->cookieOptions, app()->config->cookieEncrypt, $request->ip() ?? ''))->mirror($_COOKIE),
            (new PHPSession($request->sessionOptions)),
            (new Attribute)->mirror($_FILES),
            new Attribute($_SERVER)
        );
        return $request;
    }

    /**
     * Create request object from PSR7 Server request
     * 
     * @return self
     */
    public static function fromPsr(ServerRequestInterface $psr): self
    {
        $request = new self;
        $request->psr = $psr;
        $request->initialize(
            (new Query($psr->getQueryParams()))->setQuery($psr->getUri()->getQuery()),
            new Attribute((array)($psr->getParsedBody() ?? [])),
            (new Cookie($request->cookieOptions, app()->config->cookieEncrypt, $request->ip() ?? ''))->load($psr->getCookieParams() ?? []),
            (new PHPSession($request->sessionOptions)),
            new Upload($psr->getUploadedFiles()),
            new Attribute($psr->getServerParams())
        );

        $request->scheme = $psr->getUri()->getScheme();
        $request->domain = $psr->getUri()->getPort() ? $psr->getUri()->getHost() . ':' . $psr->getUri()->getPort() : $psr->getUri()->getHost();
        $request->host = $request->scheme  . "://" . $request->domain;
        $request->uri = '/' . ltrim($psr->getUri()->getPath(), '/');
        $request->baseUrl = $request->host;
        $request->currentUrl = $request->baseUrl . $request->uri;
        return $request;
    }

    /**
     * Sets the parameters for this request.
     *
     * This method also re-initializes all properties.
     *
     * @param StorageBagInterface  $query      - The GET parameters
     * @param StorageBagInterface  $request    - The POST parameters
     * @param StorageBagInterface  $cookies    - The COOKIE parameters
     * @param SessionStoreInterface    $session    - The SESSION parameters
     * @param UploadBagInterface|StorageBagInterface  $files      - The FILES parameters
     * @param StorageBagInterface  $server     - The SERVER parameters
     * @param StorageBagInterface  $headers    - The headers
     * @param string $content    - The raw body data
     *
     * @return self
     */
    public function initialize(
        StorageBagInterface $query = NULL,
        StorageBagInterface $request = NULL,
        StorageBagInterface $cookies = NULL,
        SessionStoreInterface  $session = NULL,
        UploadBagInterface|StorageBagInterface $files = NULL,
        StorageBagInterface $server = NULL,
        StorageBagInterface $headers = NULL,
        $content = null
    ): self {

        $this->request  =   $request ?: $this->request;
        $this->query    =   $query ?: $this->query;
        $this->session  =   $session ?: $this->session;
        $this->cookies  =   $cookies ?: $this->cookies;
        $this->files    =   $files ?: $this->files;
        $this->server   =   $server ?: $this->server;
        $this->content  =   $content ?: $this->content;

        // Load data from server vars
        if ($this->server) {

            $this->headers  =   $headers ?: new Attribute(array_change_key_case($this->getHeadersFromServer($this->server->all())));

            $this->contentType  = $this->contentType ?: $this->server->get('CONTENT_TYPE', '');
            $this->method       = $this->method ?: $this->server->get('REQUEST_METHOD', HttpMethod::GET);
            $this->protocol     = $this->protocol ?: $this->getServerPotocol();

            // Load request body
            if (
                (!$this->request || empty($this->request->all())) &&
                0 === strpos($this->contentType, 'application/x-www-form-urlencoded') &&
                in_array(strtoupper($this->method), array(HttpMethod::PUT, HttpMethod::DELETE))
            ) {
                parse_str($this->getContent(), $data);
                $this->request = new Attribute($data);
            } elseif (
                (!$this->request || empty($this->request->all())) &&
                0 === strpos($this->contentType, 'application/json') &&
                in_array(strtoupper($this->method), array(HttpMethod::POST, HttpMethod::PUT, HttpMethod::DELETE))
            ) {
                $data = json_decode($this->getContent(), true);
                $this->request = new Attribute($data);
            }

            $this->scheme = $this->scheme ?: ($this->isHttps() ? "https" : "http");
            $this->ip = $this->ip ?: $this->getIpAddress();
            $this->domain = $this->domain ?: $this->server->get('HTTP_HOST');
            $this->host = $this->host ?: $this->scheme  . "://" . $this->domain;
            if (!$this->uri) {
                $this->uri = '/' . ltrim($this->server->get('REQUEST_URI') ?: ($this->server->get('PATH_INFO') ?: $this->server->get('ORIG_PATH_INFO') ?: ''), '/');
                $this->uri = rawurldecode(explode('?', $this->uri ?? '', 2)[0]);
            }
            $this->baseUrl = $this->baseUrl ?: $this->host;
            $this->currentUrl = $this->currentUrl ?: $this->baseUrl . $this->uri;
        }

        // Start session
        if ($this->session && app()->config->httpSessionAutoStart) {
            !is_cli() && $this->session->start();
        }

        return $this;
    }

    /**
     * Change request's url. Clone request with new url
     * 
     * @param UriInterface $uri
     * @return self
     */
    public function withUri(UriInterface $uri): self
    {
        $request = clone $this;
        $request->scheme = $uri->getScheme();
        $request->domain = $uri->getPort() ? $uri->getHost() . ':' . $uri->getPort() : $uri->getHost();
        $request->host = $request->scheme  . "://" . $request->domain;
        $request->uri = '/' . ltrim($uri->getPath(), '/');
        $request->baseUrl = $request->host;
        $request->currentUrl = $request->baseUrl . $request->uri;
        return $request;
    }

    /**
     * Returns the request body content.
     *
     * @param boolean $asResource - If true, a resource will be returned
     * @return string|resource    - The request body content or a resource to read the body stream.
     *
     * @throws LogicException
     */
    public function getContent($asResource = false)
    {
        if (false === $this->content || (true === $asResource && null !== $this->content)) {
            throw new LogicException('getContent() can only be called once when using the resource return type.');
        }

        if (true === $asResource) {
            $this->content = false;

            return fopen('php://input', 'rb');
        }

        if (null === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }

    /**
     * Get server protocol or http version
     * 
     * @return string
     */
    private function getServerPotocol()
    {
        return (!empty($this->server->get('SERVER_PROTOCOL')) && in_array($this->server->get('SERVER_PROTOCOL'), array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'), TRUE))
            ? $this->server->get('SERVER_PROTOCOL') : 'HTTP/1.1';
    }

    /**
     * Check if https enabled
     * 
     * @return bool
     */
    protected function isHttps()
    {
        if (!empty($this->server->get('HTTPS')) && strtolower($this->server->get('HTTPS')) !== 'off') {
            return TRUE;
        } elseif (!empty($this->server->get('HTTP_X_FORWARDED_PROTO')) && strtolower($this->server->get('HTTP_X_FORWARDED_PROTO')) === 'https') {
            return TRUE;
        } elseif (!empty($this->server->get('HTTP_FRONT_END_HTTPS')) && strtolower($this->server->get('HTTP_FRONT_END_HTTPS')) !== 'off') {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Get Ip
     * 
     * @return string
     */
    protected function getIpAddress()
    {
        // check for shared internet/ISP IP
        if (!empty($this->server->get('HTTP_CLIENT_IP')) && $this->validateIpAddress($this->server->get('HTTP_CLIENT_IP'))) {
            return $this->server->get('HTTP_CLIENT_IP');
        }
        // check for IPs passing through proxies
        if (!empty($this->server->get('HTTP_X_FORWARDED_FOR'))) {
            // check if multiple ips exist in var
            if (strpos($this->server->get('HTTP_X_FORWARDED_FOR'), ',') !== false) {
                $iplist = explode(',', $this->server->get('HTTP_X_FORWARDED_FOR') ?? '', 20);
                foreach ($iplist as $ip) {
                    if ($this->validateIpAddress($ip))
                        return $ip;
                }
            } else {
                if ($this->validateIpAddress($this->server->get('HTTP_X_FORWARDED_FOR')))
                    return $this->server->get('HTTP_X_FORWARDED_FOR');
            }
        }
        if (!empty($this->server->get('HTTP_X_FORWARDED')) && $this->validateIpAddress($this->server->get('HTTP_X_FORWARDED')))
            return $this->server->get('HTTP_X_FORWARDED');

        if (!empty($this->server->get('HTTP_X_CLUSTER_CLIENT_IP')) && $this->validateIpAddress($this->server->get('HTTP_X_CLUSTER_CLIENT_IP')))
            return $this->server->get('HTTP_X_CLUSTER_CLIENT_IP');

        if (!empty($this->server->get('HTTP_FORWARDED_FOR')) && $this->validateIpAddress($this->server->get('HTTP_FORWARDED_FOR')))
            return $this->server->get('HTTP_FORWARDED_FOR');

        if (!empty($this->server->get('HTTP_FORWARDED')) && $this->validateIpAddress($this->server->get('HTTP_FORWARDED')))
            return $this->server->get('HTTP_FORWARDED');

        // return unreliable ip since all else failed
        return $this->server->get('REMOTE_ADDR');
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     * // TODO Validate ipv6
     * 
     * @param $ip
     * @return bool
     */
    protected function validateIpAddress($ip)
    {
        if (strtolower($ip) === 'unknown') return false;

        // generate ipv4 network address
        $ip = ip2long($ip);

        // if the ip is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {

            // make sure to get unsigned long representation of ip
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);

            // do private network range checking
            if ($ip >= 0 && $ip <= 50331647) return false;
            if ($ip >= 167772160 && $ip <= 184549375) return false;
            if ($ip >= 2130706432 && $ip <= 2147483647) return false;
            if ($ip >= 2851995648 && $ip <= 2852061183) return false;
            if ($ip >= 2886729728 && $ip <= 2887778303) return false;
            if ($ip >= 3221225984 && $ip <= 3221226239) return false;
            if ($ip >= 3232235520 && $ip <= 3232301055) return false;
            if ($ip >= 4294967040) return false;
        }

        return true;
    }

    /**
     * Get request headers from Server Variables
     * 
     * @param array $server
     * @return array
     */
    protected function getHeadersFromServer(array $server)
    {
        $headers = array();
        foreach ($server as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) {
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
     * @return mixed
     */
    public function ip()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function scheme()
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function domain()
    {
        return $this->domain;
    }

    /**
     * @return string
     */
    public function host()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function baseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function currentUrl()
    {
        return $this->currentUrl;
    }

    /**
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return $this->contentType;
    }

    /**
     * @return mixed
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * Get all of the segments for the request path.
     *
     * @return array
     */
    public function segments()
    {
        $segments = explode('/', $this->uri() ?? '', 100);
        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }

    /**
     *
     * @return StorageBagInterface
     */
    public function file(): StorageBagInterface
    {
        return $this->files;
    }

    /**
     *
     * @return SessionStoreInterface
     */
    public function session(): SessionStoreInterface
    {
        return $this->session;
    }

    /**
     *
     * @return StorageBagInterface
     */
    public function cookie(): StorageBagInterface
    {
        return $this->cookies;
    }

    /**
     *
     * @return StorageBagInterface
     */
    public function query(): StorageBagInterface
    {
        return $this->query;
    }

    /**
     *
     * @return StorageBagInterface
     */
    public function request(): StorageBagInterface
    {
        return $this->request;
    }

    /**
     *
     * @return StorageBagInterface
     */
    public function server(): StorageBagInterface
    {
        return $this->server;
    }

    /**
     *
     * @return StorageBagInterface
     */
    public function header(): StorageBagInterface
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function version()
    {
        return $this->protocol ? str_replace('HTTP/', '', $this->protocol) : '1.1';
    }

    /**
     * Set the value of session
     *
     * @return  self
     */
    public function setSession(SessionStoreInterface $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Set the value of request
     *
     * @return  self
     */
    public function setRequest(StorageBagInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the value of query
     *
     * @return  self
     */
    public function setQuery(StorageBagInterface $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the value of server
     *
     * @return  self
     */
    public function setServer(StorageBagInterface $server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Set the value of files
     *
     * @return  self
     */
    public function setFiles(UploadBagInterface|StorageBagInterface $files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Set the value of cookies
     *
     * @return  self
     */
    public function setCookies(StorageBagInterface $cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * Set the value of headers
     *
     * @return  self
     */
    public function setHeaders(StorageBagInterface $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get PSR7 Server request
     * 
     * @return ServerRequestInterface
     */
    function toPsr(): ServerRequestInterface
    {
        $request = $this->psr ?? new \Nyholm\Psr7\ServerRequest(
            $this->method,
            (new Uri($this->currentUrl))->withQuery(strval($this->query)),
            $this->headers ? $this->headers->all() : [],
            $this->content ?: strval($this->request),
            $this->version(),
            $this->server ? $this->server->all() : [],
        );
        return $request;
    }
}
