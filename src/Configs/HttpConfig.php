<?php

namespace Busarm\PhpMini\Configs;

use Busarm\PhpMini\Enums\ResponseFormat;
use Busarm\PhpMini\Interfaces\ConfigurationInterface;
use Busarm\PhpMini\Traits\CustomConfig;

/**
 * Application Configuration
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license s://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class HttpConfig implements ConfigurationInterface
{
    use CustomConfig;


    /**
     * CORS Check
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
     * are hosting your API on a different domain from the application that
     * will access it through a browser
     *
     * @var bool
     */
    public bool $checkCors = false;

    /**
     * CORS Allow Any Domain
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
     * source domain
     *
     * @var bool
     */
    public bool $allowAnyCorsDomain = false;

    /**
     * CORS Allowable Domains
     * Set the allowable domains within the array
     * e.g. ['://www.example.com', 's://spa.example.com']
     *
     * @var array
     */
    public array $allowedCorsOrigins = [];

    /**
     * CORS Allowable Headers
     * If using CORS checks, set the allowable headers here
     *
     * @var array
     */
    public array $allowedCorsHeaders = [];

    /**
     * CORS Allowable Methods
     * If using CORS checks, you can set the methods you want to be allowed
     *
     * @var array
     */
    public array $allowedCorsMethods = [];

    /**
     * CORS Exposed Headers
     * If using CORS checks, set the headers permitted to be sent to client here
     *
     * @var array
     */
    public array $exposedCorsHeaders = [];

    /**
     * CORS Max Age
     * How long in seconds to cache CORS preflight response in browser.
     * -1 for disabling caching.
     *
     * @var int
     */
    public int $corsMaxAge = -1;

    /**
     * Send HTTP response without exiting. `json`|`xml`
     * 
     * @var bool
     */
    public bool $sendAndContinue = false;

    /**
     * HTPP default response format
     * 
     * @var \Busarm\PhpMini\Enums\ResponseFormat::*
     */
    public string $responseFormat = ResponseFormat::JSON;


    /**
     * Set will access it through a browser
     *
     * @param  bool  $checkCors  will access it through a browser
     *
     * @return  self
     */
    public function setCheckCors(bool $checkCors)
    {
        $this->checkCors = $checkCors;

        return $this;
    }

    /**
     * Set source domain
     *
     * @param  bool  $allowAnyCorsDomain  source domain
     *
     * @return  self
     */
    public function setAllowAnyCorsDomain(bool $allowAnyCorsDomain)
    {
        $this->allowAnyCorsDomain = $allowAnyCorsDomain;

        return $this;
    }

    /**
     * Set e.g. ['://www.example.com', 's://spa.example.com']
     *
     * @param  array  $allowedCorsOrigins  e.g. ['://www.example.com', 's://spa.example.com']
     *
     * @return  self
     */
    public function setAllowedCorsOrigins(array $allowedCorsOrigins)
    {
        $this->allowedCorsOrigins = $allowedCorsOrigins;

        return $this;
    }

    /**
     * Set if using CORS checks, you can set the methods you want to be allowed
     *
     * @param  array  $allowedCorsMethods  If using CORS checks, you can set the methods you want to be allowed
     *
     * @return  self
     */
    public function setAllowedCorsMethods(array $allowedCorsMethods)
    {
        $this->allowedCorsMethods = $allowedCorsMethods;

        return $this;
    }

    /**
     * Set if using CORS checks, set the allowable headers here
     *
     * @param  array  $allowedCorsHeaders  If using CORS checks, set the allowable headers here
     *
     * @return  self
     */
    public function setAllowedCorsHeaders(array $allowedCorsHeaders)
    {
        $this->allowedCorsHeaders = $allowedCorsHeaders;

        return $this;
    }

    /**
     * Set if using CORS checks, set the headers permitted to be sent to client here
     *
     * @param  array  $exposedCorsHeaders  If using CORS checks, set the headers permitted to be sent to client here
     *
     * @return  self
     */
    public function setExposedCorsHeaders(array $exposedCorsHeaders)
    {
        $this->exposedCorsHeaders = $exposedCorsHeaders;

        return $this;
    }

    /**
     * Set -1 for disabling caching.
     *
     * @param  int  $corsMaxAge  -1 for disabling caching.
     *
     * @return  self
     */
    public function setCorsMaxAge(int $corsMaxAge)
    {
        $this->corsMaxAge = $corsMaxAge;

        return $this;
    }

    /**
     * Set send HTTP response without exiting
     *
     * @param  bool  $sendAndContinue  Send HTTP response without exiting
     *
     * @return  self
     */
    public function setSendAndContinue(bool $sendAndContinue)
    {
        $this->sendAndContinue = $sendAndContinue;

        return $this;
    }

    /**
     * Set HTPP default response format
     *
     * @param \Busarm\PhpMini\Enums\ResponseFormat::*  $responseFormat  HTPP default response format. `json`|`xml`
     *
     * @return  self
     */
    public function setResponseFormat(string $responseFormat)
    {
        $this->responseFormat = $responseFormat;

        return $this;
    }
}
