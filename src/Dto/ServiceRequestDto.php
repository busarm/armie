<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ServiceRequestDto extends BaseDto
{
    /** @var string Name of service */
    public string|null $name = null;
    /** @var string Location endpoint of service if available */
    public string|null $location = null;
    /** @var string Route pathe to resource in service */
    public string|null $route = null;
    /** @var \Busarm\PhpMini\Enums\ServiceType::* Service resource request type */
    public string|null $type = null;
    /** @var array<string,string> Service params */
    public array $params = [];
    /** @var array<string,string> Service headers */
    public array $headers = [];
    /** @var array<string,\Psr\Http\Message\StreamInterface|resource|string> Service files */
    public array $files = [];


    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the value of location
     *
     * @return  self
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

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
     * @param \Busarm\PhpMini\Enums\ServiceType::* $type
     * @return  self
     */
    public function setType($type)
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
