<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Configs\HttpConfig;
use Busarm\PhpMini\Configs\PDOConfig;
use Busarm\PhpMini\Enums\CacheLimiter;
use Busarm\PhpMini\Enums\SameSite;
use Busarm\PhpMini\Enums\Verbose;
use Busarm\PhpMini\Interfaces\ConfigurationInterface;
use Busarm\PhpMini\Traits\CustomConfig;
use SessionHandlerInterface;

/**
 * Application Configuration
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Config implements ConfigurationInterface
{
    use CustomConfig;

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
     * App secret key
     *
     * @var string|null
     */
    public string|null $secret = NULL;


    // ------------- COOKIE -----------------//


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
     * @var SameSite
     */
    public SameSite $cookieSameSite = SameSite::LAX;


    // ------------- SESSION -----------------//


    /**
     * Session Enabled
     *
     * @var bool
     */
    public bool $sessionEnabled = false;

    /**
     * Session save path
     * (Without trailing slash)
     *
     * @var string
     */
    public string $sessionPath = '';

    /**
     * Session Name
     *
     * @var string
     */
    public string|null $sessionName = null;


    /**
     * Session Lifetime (seconds). e.g 10 for 10 seconds
     *
     * @var int
     */
    public int|null $sessionLifetime = null;

    /**
     * Session Handler
     *
     * @var SessionHandlerInterface
     */
    public SessionHandlerInterface|null $sessionHandler = null;


    // ------------- CACHE -----------------//


    /**
     * Cache Limiter
     *
     * @var CacheLimiter
     */
    public CacheLimiter $cacheLimiter = CacheLimiter::NO_CACHE;


    // ------------- LOG -----------------//


    /**
     * Log request info for every request
     *
     * @var boolean
     */
    public bool $logRequest = false;

    /**
     * Logger verbosity
     * 
     * @var Verbose
     */
    public Verbose $loggerVerborsity = Verbose::DEBUG;


    // ------------- SSL -----------------//

    /**
     * SSL is enabled
     *
     * @var bool
     */
    public bool $sslEnabled = false;

    /**
     * SSL certificate path
     *
     * @var string|null
     */
    public string|null $sslCertPath = null;

    /**
     * SSL primary key path
     *
     * @var string|null
     */
    public string|null $sslPkPath = null;

    /**
     * SSL verify peer
     *
     * @var bool
     */
    public bool $sslVerifyPeer = false;


    // ------------- HTTP -----------------//

    /**
     * HTTP Configurations
     *
     * @var HttpConfig
     */
    public HttpConfig $http;


    // ------------- DATABASE -----------------//

    /**
     * Database Configurations
     *
     * @var PDOConfig
     */
    public PDOConfig $db;


    public function __construct()
    {
        $prefix =  str_replace(' ', '_', strtolower($this->name));
        $this->setTempPath(sys_get_temp_dir() . "/$prefix");
        $this->setCachePath($this->tempPath . '/cache');
        $this->setSessionPath($this->tempPath . '/session');
        $this->setUploadPath($this->tempPath . '/upload');
        $this->setHttp(new HttpConfig);
        $this->setDb(new PDOConfig);
    }

    /**
     * Get cookie configs
     *
     * @return array
     */
    public function getSessionConfigs(): array
    {
        return [
            'cookie_lifetime' => $this->sessionLifetime ?: $this->cookieDuration,
            'cookie_path' => $this->cookiePath,
            'cookie_domain' => $this->cookieDomain,
            'cookie_secure' => $this->cookieSecure,
            'cookie_httponly' => $this->cookieHttpOnly,
            'cookie_samesite' => $this->cookieSameSite->value,
            'cache_limiter' => $this->cacheLimiter->value,
            'save_path' => $this->sessionPath,
            'name' => $this->sessionName ?? str_replace(' ', '_', strtolower($this->name)) . '_sess'
        ];
    }

    /**
     * Get cookie configs
     *
     * @return array
     */
    public function getCookieConfigs(): array
    {
        return [
            'expires' => time() + $this->cookieDuration,
            'path' => $this->cookiePath,
            'domain' => $this->cookieDomain,
            'secure' => $this->cookieSecure,
            'httponly' => $this->cookieHttpOnly,
            'samesite' => $this->cookieSameSite->value,
        ];
    }


    // --------------- Setters ------------------- //


    /**
     * Add custom config file
     * 
     * @param string $config Config file name/path relative to Config Path @see self::setConfigPath
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
     * @param array $configs List of config file name/path relative to Config Path @see self::setConfigPath
     * @return self
     */
    public function addFiles($configs = array())
    {
        $this->files = array_merge($this->files, $configs);
        return $this;
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
     * Set path to view folder - relative to system base path or app folder. e.g `/var/www/html/view` or `view` - ('/var/www/html/app/view')
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
     * Set secret key
     *
     * @param  string  $secret
     *
     * @return  self
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

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
     * @param  SameSite  $cookieSameSite  Cookie Same Site Policy
     *
     * @return  self
     */
    public function setCookieSameSite(SameSite $cookieSameSite)
    {
        $this->cookieSameSite = $cookieSameSite;

        return $this;
    }


    /**
     * Set session Handler
     *
     * @param  SessionHandlerInterface  $sessionHandler  Session Handler
     *
     * @return  self
     */
    public function setSessionHandler(SessionHandlerInterface $sessionHandler)
    {
        $this->sessionHandler = $sessionHandler;

        return $this;
    }

    /**
     * Set session Name
     *
     * @param  string  $sessionName  Session Name
     *
     * @return  self
     */
    public function setSessionName(string $sessionName)
    {
        $this->sessionName = $sessionName;

        return $this;
    }

    /**
     * Set cache Limiter
     *
     * @param  CacheLimiter  $cacheLimiter  Cache Limiter
     *
     * @return  self
     */
    public function setCacheLimiter(CacheLimiter $cacheLimiter)
    {
        $this->cacheLimiter = $cacheLimiter;

        return $this;
    }


    /**
     * Set log request info for every request
     *
     * @param  boolean  $logRequest  Log request info for every request
     *
     * @return  self
     */
    public function setLogRequest(bool $logRequest)
    {
        $this->logRequest = $logRequest;

        return $this;
    }
    /**
     * Set logger verbosity
     *
     * @param  Verbose  $loggerVerborsity  Logger verbosity
     *
     * @return  self
     */
    public function setLoggerVerborsity(Verbose $loggerVerborsity)
    {
        $this->loggerVerborsity = $loggerVerborsity;

        return $this;
    }

    /**
     * Set session lifetime (seconds). e.g 10 for 10 seconds
     *
     * @param  int  $sessionLifetime  Session Lifetime (seconds). e.g 10 for 10 seconds
     *
     * @return  self
     */
    public function setSessionLifetime(int $sessionLifetime)
    {
        $this->sessionLifetime = $sessionLifetime;

        return $this;
    }

    /**
     * Set session enabled
     *
     * @param  bool  $sessionEnabled  Session Enabled
     *
     * @return  self
     */
    public function setSessionEnabled(bool $sessionEnabled)
    {
        $this->sessionEnabled = $sessionEnabled;

        return $this;
    }

    /**
     * Set sSL is enabled
     *
     * @param  bool  $sslEnabled  SSL is enabled
     *
     * @return  self
     */
    public function setSslEnabled(bool $sslEnabled)
    {
        $this->sslEnabled = $sslEnabled;

        return $this;
    }

    /**
     * Set sSL certificate path
     *
     * @param  string|null  $sslCertPath  SSL certificate path
     *
     * @return  self
     */
    public function setSslCertPath($sslCertPath)
    {
        $this->sslCertPath = $sslCertPath;

        return $this;
    }

    /**
     * Set sSL primary key path
     *
     * @param  string|null  $sslPkPath  SSL primary key path
     *
     * @return  self
     */
    public function setSslPkPath($sslPkPath)
    {
        $this->sslPkPath = $sslPkPath;

        return $this;
    }

    /**
     * Set sSL verify peer
     *
     * @param  bool  $sslVerifyPeer  SSL verify peer
     *
     * @return  self
     */
    public function setSslVerifyPeer(bool $sslVerifyPeer)
    {
        $this->sslVerifyPeer = $sslVerifyPeer;

        return $this;
    }

    /**
     * Set hTTP Configurations
     *
     * @param  HttpConfig  $http  HTTP Configurations
     *
     * @return  self
     */
    public function setHttp(HttpConfig $http)
    {
        $this->http = $http;

        return $this;
    }

    /**
     * Set database Configurations
     *
     * @param  PDOConfig  $db  Database Configurations
     *
     * @return  self
     */
    public function setDb(PDOConfig $db)
    {
        $this->db = $db;

        return $this;
    }
}
