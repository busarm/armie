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
    /** @var string */
    public string|null $name;
    /** @var string */
    public string|null $route;
    /** @var string @see \Busarm\PhpMini\Enums\ServiceType */
    public string|null $type;
    /** @var array */
    public array|null $params;
    public array|null $headers;


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
     * @param string $type @see \Busarm\PhpMini\Enums\ServiceType
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
