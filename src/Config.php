<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Enums\ResponseFormat;
use Busarm\PhpMini\Enums\Verbose;

/**
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
     * Path to app folder - relative to system base path. e.g '/var/www/html/app'
     * (Without leading or trailing slash)
     *
     * @var string
     */
    public string $appPath = '';

    /**
     * Path to view folder - relative to app folder.
     * (Without leading or trailing slash)
     *
     * @var string
     */
    public string $viewPath = '';

    /**
     * Path to custom config folder - relative to app folder. 
     * (Without leading or trailing slash)
     *
     * @var string
     */
    public string $configPath = '';

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
     * Path to app folder - relative to system base path. e.g '/var/www/html/app'
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
     * Set path to view folder - relative to app folder
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
     * Set path to custom config files - relative to app folder
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
