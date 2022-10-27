<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Enums\CacheLimiter;
use Busarm\PhpMini\Enums\ResponseFormat;
use Busarm\PhpMini\Enums\SameSite;
use Busarm\PhpMini\Enums\Verbose;

/**
 * Application Configuration
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Config
{
    /** 
     * Custom configs
     * 
     * @var array 
     */
    protected array $custom = [];

    /** 
     * Custom config files
     * 
     * @var array 
     */
    public array $files = [];

    /**
     * App name
     *
     * @var string
     */
    public string $name = 'PHP Mini';

    /**
     * App version
     *
     * @var string
     */
    public string $version = '1.0.0';

    /**
     * Path to temporary folder
     * (Without trailing slash)
     *
     * @var string
     */
    public string $tempPath = '';

    /**
     * Path to cache folder
     * (Without trailing slash)
     *
     * @var string
     */
    public string $cachePath = '';

    /**
     * Path to upload folder
     * (Without trailing slash)
     *
     * @var string
     */
    public string $uploadPath = '';

    /**
     * Path to session folder
     * (Without trailing slash)
     *
     * @var string
     */
    public string $sessionPath = '';

    /**
     * Path to app folder - relative to system base path. e.g `/var/www/html/app`
     * (Without leading or trailing slash)
     *
     * @var string
     */
    public string $appPath = '';

    /**
     * Path to view folder - relative to system base path or app folder.
     * (Without leading or trailing slash)
     *
     * @var string
     */
    public string $viewPath = '';

    /**
     * Path to custom config folder - relative to system base path or app folder.
     * (Without leading or trailing slash)
     *
     * @var string
     */
    public string $configPath = '';

    /**
     * App encryption Key
     *
     * @var string|null
     */
    public string|null $encryptionKey = NULL;

    /**
     * Cookie Prefix
     *
     * @var string|null
     */
    public string|null $cookiePrefix = NULL;
    /**
     * Cookie Duration in seconds
     *
     * @var int
     */
    public int $cookieDuration = 3600;
    /**
     * Cookie Path
     *
     * @var string
     */
    public string $cookiePath = '/';
    /**
     * Cookie Domain
     *
     * @var string
     */
    public string $cookieDomain = '';
    /**
     * Cookie Secure: Use with secure HTTPS connections only
     *
     * @var bool
     */
    public bool $cookieSecure = false;
    /**
     * Cookie Http Only: Use with HTTP requests only - can't be accessed via browser script
     *
     * @var bool
     */

    public bool $cookieHttpOnly = false;
    /**
     * Cookie should be encyrpted
     *
     * @var bool
     */
    public bool $cookieEncrypt = true;
    /**
     * Cookie Same Site Policy
     * 
     * @see \Busarm\PhpMini\Enums\SameSite
     * @var string
     */
    public string $cookieSameSite = SameSite::LAX;

    /**
     * Cache Limiter
     *
     * @see \Busarm\PhpMini\Enums\CacheLimiter
     * @var string
     */
    public string $cacheLimiter = CacheLimiter::NO_CACHE;

    /**
     * Logger verbosity
     * 
     * @see \Busarm\PhpMini\Enums\Verbose
     * @var int
     */
    public int $loggerVerborsity = Verbose::DEBUG;

    /**
     * CORS Check
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
     * are hosting your API on a different domain from the application that
     * will access it through a browser
     *
     * @var bool
     */
    public bool $httpCheckCors = false;

    /**
     * CORS Allow Any Domain
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
     * source domain
     *
     * @var bool
     */
    public bool $httpAllowAnyCorsDomain = false;

    /**
     * CORS Allowable Domains
     * Set the allowable domains within the array
     * e.g. ['http://www.example.com', 'https://spa.example.com']
     *
     * @var array
     */
    public array $httpAllowedCorsOrigins = [];

    /**
     * CORS Allowable Headers
     * If using CORS checks, set the allowable headers here
     *
     * @var array
     */
    public array $httpAllowedCorsHeaders = [];

    /**
     * CORS Allowable Methods
     * If using CORS checks, you can set the methods you want to be allowed
     *
     * @var array
     */
    public array $httpAllowedCorsMethods = [];

    /**
     * CORS Exposed Headers
     * If using CORS checks, set the headers permitted to be sent to client here
     *
     * @var array
     */
    public array $httpExposedCorsHeaders = [];

    /**
     * CORS Max Age
     * How long in seconds to cache CORS preflight response in browser.
     * -1 for disabling caching.
     *
     * @var int
     */
    public int $httpCorsMaxAge = -1;

    /**
     * Send HTTP response without exiting. `json`|`xml`
     * 
     * @var bool
     */
    public bool $httpSendAndContinue = false;

    /**
     * HTPP default response format
     * 
     * @see \Busarm\PhpMini\Enums\ResponseFormat
     * @var string
     */
    public string $httpResponseFormat = ResponseFormat::JSON;

    /**
     * Auto start session for HTTP request
     *
     * @var bool
     */
    public bool $httpSessionAutoStart = true;


    function __construct()
    {
        $prefix =  str_replace(' ', '_', strtolower($this->name));
        $this->setTempPath(sys_get_temp_dir() . "/$prefix");
        $this->setCachePath($this->tempPath . '/cache');
        $this->setSessionPath($this->tempPath . '/session');
        $this->setUploadPath($this->tempPath . '/upload');
    }

    /**
     * Set app name
     *
     * @param  string  $name  App name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set app version
     *
     * @param  string  $version  App version
     *
     * @return  self
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set (Without trailing slash)
     *
     * @param  string  $tempPath  (Without trailing slash)
     *
     * @return  self
     */
    public function setTempPath(string $tempPath)
    {
        $this->tempPath = $tempPath;
        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }

        return $this;
    }

    /**
     * Set (Without trailing slash)
     *
     * @param  string  $cachePath  (Without trailing slash)
     *
     * @return  self
     */
    public function setCachePath(string $cachePath)
    {
        $this->cachePath = $cachePath;
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        return $this;
    }

    /**
     * Set (Without trailing slash)
     *
     * @param  string  $sessionPath  (Without trailing slash)
     *
     * @return  self
     */
    public function setSessionPath(string $sessionPath)
    {
        $this->sessionPath = $sessionPath;
        if (!is_dir($this->sessionPath)) {
            mkdir($this->sessionPath, 0755, true);
        }

        return $this;
    }

    /**
     * Set (Without trailing slash)
     *
     * @param  string  $uploadPath  (Without trailing slash)
     *
     * @return  self
     */
    public function setUploadPath(string $uploadPath)
    {
        $this->uploadPath = $uploadPath;
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }

        return $this;
    }

    /**
     * Set path to app folder - relative to system base path. e.g `/var/www/html/app`
     *
     * @param  string  $appPath  (Without leading or trailing slash)
     *
     * @return  self
     */
    public function setAppPath($appPath)
    {
        $this->appPath = $appPath;

        return $this;
    }

    /**
     * Set path to view folder - relative to system base path or app folder. e.g `/var/www/html/view` or `view` ('/var/www/html/app/view')
     *
     * @param  string  $viewPath  (Without leading or trailing slash)
     *
     * @return  self
     */
    public function setViewPath($viewPath)
    {
        $this->viewPath = $viewPath;

        return $this;
    }

    /**
     * Set path to custom config files - relative to system base path or app folder. e.g `/var/www/html/config` or `config` ('/var/www/html/app/config')
     *
     * @param  string  $configPath  (Without leading or trailing slash)
     *
     * @return  self
     */
    public function setConfigPath($configPath)
    {
        $this->configPath = $configPath;

        return $this;
    }

    /**
     * Set encryption Key
     *
     * @param  string  $encryptionKey
     *
     * @return  self
     */
    public function setEncryptionKey($encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;

        return $this;
    }

    /**
     * Set cookie prefix
     *
     * @param  string  $cookiePrefix
     *
     * @return  self
     */
    public function setCookiePrefix($cookiePrefix)
    {
        $this->cookiePrefix = $cookiePrefix;

        return $this;
    }

    /**
     * Set cookie duration
     *
     * @param  int  $cookieDuration
     *
     * @return  self
     */
    public function setCookieDuration($cookieDuration)
    {
        $this->cookieDuration = $cookieDuration;

        return $this;
    }

    /**
     * Set cookie path
     *
     * @param  string  $cookiePath
     *
     * @return  self
     */
    public function setCookiePath($cookiePath)
    {
        $this->cookiePath = $cookiePath;

        return $this;
    }

    /**
     * Set cookie domain
     *
     * @param  string  $cookieDomain
     *
     * @return  self
     */
    public function setCookieDomain($cookieDomain)
    {
        $this->cookieDomain = $cookieDomain;

        return $this;
    }

    /**
     * Cookie Secure: Use with secure HTTPS connections only
     *
     * @param  bool  $cookieSecure  Cookie Secure: Use with secure HTTPS connections only
     *
     * @return  self
     */
    public function setCookieSecure(bool $cookieSecure)
    {
        $this->cookieSecure = $cookieSecure;

        return $this;
    }

    /**
     * Set cookie Http Only: Use with HTTP requests only - can't be accessed via browser script
     *
     * @param  bool  $cookieHttpOnly  Cookie Http Only: Use with HTTP requests only - can't be accessed via browser script
     *
     * @return  self
     */
    public function setCookieHttpOnly(bool $cookieHttpOnly)
    {
        $this->cookieHttpOnly = $cookieHttpOnly;

        return $this;
    }

    /**
     * Set cookie should be encyrpted
     *
     * @param  bool  $cookieEncrypt  Cookie should be encyrpted
     *
     * @return  self
     */
    public function setCookieEncrypt(bool $cookieEncrypt)
    {
        $this->cookieEncrypt = $cookieEncrypt;

        return $this;
    }

    /**
     * Set cookie Same Site Policy
     *
     * @param  string  $cookieSameSite  Cookie Same Site Policy
     *
     * @return  self
     */
    public function setCookieSameSite(string $cookieSameSite)
    {
        $this->cookieSameSite = $cookieSameSite;

        return $this;
    }

    /**
     * Set cache Limiter
     *
     * @param  string  $cacheLimiter  Cache Limiter
     *
     * @return  self
     */
    public function setCacheLimiter(string $cacheLimiter)
    {
        $this->cacheLimiter = $cacheLimiter;

        return $this;
    }

    /**
     * Set logger verbosity
     * 
     * @see \Busarm\PhpMini\Enums\Verbose
     * @param  int  $loggerVerborsity  Logger verbosity
     *
     * @return  self
     */
    public function setLoggerVerborsity($loggerVerborsity)
    {
        $this->loggerVerborsity = $loggerVerborsity;

        return $this;
    }

    /**
     * Set will access it through a browser
     *
     * @param  bool  $httpCheckCors  will access it through a browser
     *
     * @return  self
     */
    public function setHttpCheckCors($httpCheckCors)
    {
        $this->httpCheckCors = $httpCheckCors;

        return $this;
    }

    /**
     * Set source domain
     *
     * @param  bool  $httpAllowAnyCorsDomain  source domain
     *
     * @return  self
     */
    public function setHttpAllowAnyCorsDomain($httpAllowAnyCorsDomain)
    {
        $this->httpAllowAnyCorsDomain = $httpAllowAnyCorsDomain;

        return $this;
    }

    /**
     * Set e.g. ['http://www.example.com', 'https://spa.example.com']
     *
     * @param  array  $httpAllowedCorsOrigins  e.g. ['http://www.example.com', 'https://spa.example.com']
     *
     * @return  self
     */
    public function setHttpAllowedCorsOrigins($httpAllowedCorsOrigins)
    {
        $this->httpAllowedCorsOrigins = $httpAllowedCorsOrigins;

        return $this;
    }

    /**
     * Set if using CORS checks, you can set the methods you want to be allowed
     *
     * @param  array  $httpAllowedCorsMethods  If using CORS checks, you can set the methods you want to be allowed
     *
     * @return  self
     */
    public function setHttpAllowedCorsMethods($httpAllowedCorsMethods)
    {
        $this->httpAllowedCorsMethods = $httpAllowedCorsMethods;

        return $this;
    }

    /**
     * Set if using CORS checks, set the allowable headers here
     *
     * @param  array  $httpAllowedCorsHeaders  If using CORS checks, set the allowable headers here
     *
     * @return  self
     */
    public function setHttpAllowedCorsHeaders($httpAllowedCorsHeaders)
    {
        $this->httpAllowedCorsHeaders = $httpAllowedCorsHeaders;

        return $this;
    }

    /**
     * Set if using CORS checks, set the headers permitted to be sent to client here
     *
     * @param  array  $httpExposedCorsHeaders  If using CORS checks, set the headers permitted to be sent to client here
     *
     * @return  self
     */
    public function setHttpExposedCorsHeaders($httpExposedCorsHeaders)
    {
        $this->httpExposedCorsHeaders = $httpExposedCorsHeaders;

        return $this;
    }

    /**
     * Set -1 for disabling caching.
     *
     * @param  int  $httpCorsMaxAge  -1 for disabling caching.
     *
     * @return  self
     */
    public function setHttpCorsMaxAge($httpCorsMaxAge)
    {
        $this->httpCorsMaxAge = $httpCorsMaxAge;

        return $this;
    }

    /**
     * Set send HTTP response without exiting
     *
     * @param  bool  $httpSendAndContinue  Send HTTP response without exiting
     *
     * @return  self
     */
    public function setHttpSendAndContinue($httpSendAndContinue)
    {
        $this->httpSendAndContinue = $httpSendAndContinue;

        return $this;
    }

    /**
     * Set HTPP default response format
     *
     * @param  string  $httpResponseFormat  HTPP default response format. `json`|`xml`
     *
     * @return  self
     */
    public function setHttpResponseFormat($httpResponseFormat)
    {
        $this->httpResponseFormat = $httpResponseFormat;

        return $this;
    }

    /**
     * Set auto start session for HTTP request
     *
     * @param  bool  $httpSessionAutoStart  Auto start session for HTTP request
     *
     * @return  self
     */
    public function setHttpSessionAutoStart(bool $httpSessionAutoStart)
    {
        $this->httpSessionAutoStart = $httpSessionAutoStart;

        return $this;
    }

    /**
     * Add custom config file
     * 
     * @param string $config Config file name/path relative to Config Path (@see `self::setConfigPath`)
     * @return self
     */
    public function addFile(string $config)
    {
        $this->files[] = $config;
        return $this;
    }

    /**
     * Add custom config files
     * 
     * @param array $configs List of config file name/path relative to Config Path (@see `self::setConfigPath`)
     * @return self
     */
    public function addFiles($configs = array())
    {
        $this->files = array_merge($this->files, $configs);
        return $this;
    }

    /**
     * Get custom config
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed Returns config value or default
     */
    public function get(string $name, $default = null)
    {
        return $this->custom[$name] ?? $default;
    }

    /**
     * Set custom config
     * 
     * @param string $name
     * @param mixed $value
     * @return mixed Returns value
     */
    public function set(string $name, $value = null)
    {
        return $this->custom[$name] = $value;
    }
}
