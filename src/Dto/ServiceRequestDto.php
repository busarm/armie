<?php

namespace Armie\Dto;

use Armie\Enums\ServiceType;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ServiceRequestDto extends BaseDto
{
    /** @var string Route pathe to resource in service */
    public string|null $route = null;
    /** @var ServiceType Service resource request type */
    public ServiceType|null $type = null;
    /** @var array<string,string> Service params */
    public array $params = [];
    /** @var array<string,string> Service headers */
    public array $headers = [];
    /** @var array<string,\Psr\Http\Message\StreamInterface|resource|string> Service files */
    public array $files = [];


    /**
     * Set the value of route
     *
     * @return  self
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Set the value of type
     * 
     * @param ServiceType $type
     * @return  self
     */
    public function setType(ServiceType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the value of params
     *
     * @return  self
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Set the value of headers
     *
     * @return  self
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }
}
