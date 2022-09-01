<?php

namespace Busarm\PhpMini;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class Config
{
    /**
     * App name
     *
     * @var string|null
     */
    public string|null $name = 'PHP Mini';

    /**
     * App version
     *
     * @var string|null
     */
    public string|null $version = '1.0.0';

    /**
     * System base path to service. e.g '/var/www/html'
     * (Without trailing slash)
     *
     * @var string|null
     */
    public string|null $basePath;

    /**
     * Relative path to app folder. e.g 'app'
     * (Without leading or trailing slash)
     *
     * @var string|null
     */
    public string|null $appPath;

    /**
     * Set path to view files - relative to app folder.
     * (Without leading or trailing slash)
     *
     * @var string|null
     */
    public string|null $viewPath;

    /**
     * Set path to config files - relative to app folder. 
     * (Without leading or trailing slash)
     *
     * @var string|null
     */
    public string|null $configPath;

    /**
     * CORS Check
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
     * are hosting your API on a different domain from the application that
     * will access it through a browser
     *
     * @var bool|null
     */
    public bool|null $httpCheckCors;

    /**
     * CORS Allow Any Domain
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
     * source domain
     *
     * @var bool|null
     */
    public bool|null $httpAllowAnyCorsDomain;

    /**
     * CORS Allowable Domains
     * Set the allowable domains within the array
     * e.g. ['http://www.example.com', 'https://spa.example.com']
     *
     * @var array|null
     */
    public array|null $httpAllowedCorsOrigins;

    /**
     * CORS Allowable Headers
     * If using CORS checks, set the allowable headers here
     *
     * @var array|null
     */
    public array|null $httpAllowedCorsHeaders;

    /**
     * CORS Allowable Methods
     * If using CORS checks, you can set the methods you want to be allowed
     *
     * @var array|null
     */
    public array|null $httpAllowedCorsMethods;

    /**
     * CORS Exposed Headers
     * If using CORS checks, set the headers permitted to be sent to client here
     *
     * @var array|null
     */
    public array|null $httpExposedCorsHeaders;

    /**
     * CORS Max Age
     * How long in seconds to cache CORS preflight response in browser.
     * -1 for disabling caching.
     *
     * @var int|null
     */
    public int|null $httpCorsMaxAge;


    /**
     * Set app name
     *
     * @param  string|null  $name  App name
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
     * @param  string|null  $version  App version
     *
     * @return  self
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set (Without leading or trailing slash)
     *
     * @param  string|null  $basePath  (Without leading or trailing slash)
     *
     * @return  self
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Set (Without leading or trailing slash)
     *
     * @param  string|null  $appPath  (Without leading or trailing slash)
     *
     * @return  self
     */
    public function setAppPath($appPath)
    {
        $this->appPath = $appPath;

        return $this;
    }

    /**
     * Set (Without leading or trailing slash)
     *
     * @param  string|null  $viewPath  (Without leading or trailing slash)
     *
     * @return  self
     */
    public function setViewPath($viewPath)
    {
        $this->viewPath = $viewPath;

        return $this;
    }

    /**
     * Set (Without leading or trailing slash)
     *
     * @param  string|null  $configPath  (Without leading or trailing slash)
     *
     * @return  self
     */
    public function setConfigPath($configPath)
    {
        $this->configPath = $configPath;

        return $this;
    }

    /**
     * Set will access it through a browser
     *
     * @param  bool|null  $httpCheckCors  will access it through a browser
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
     * @param  bool|null  $httpAllowAnyCorsDomain  source domain
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
     * @param  array|null  $httpAllowedCorsOrigins  e.g. ['http://www.example.com', 'https://spa.example.com']
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
     * @param  array|null  $httpAllowedCorsMethods  If using CORS checks, you can set the methods you want to be allowed
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
     * @param  array|null  $httpAllowedCorsHeaders  If using CORS checks, set the allowable headers here
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
     * @param  array|null  $httpExposedCorsHeaders  If using CORS checks, set the headers permitted to be sent to client here
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
     * @param  int|null  $httpCorsMaxAge  -1 for disabling caching.
     *
     * @return  self
     */
    public function setHttpCorsMaxAge($httpCorsMaxAge)
    {
        $this->httpCorsMaxAge = $httpCorsMaxAge;

        return $this;
    }
}
