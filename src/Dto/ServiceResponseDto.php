<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ServiceResponseDto extends BaseDto
{
    public bool $status = false;
    public bool $async = false;
    public int $code = 0;
    public array $data = [];

    /**
     * Set the value of status
     *
     * @return  self
     */
    public function setStatus(bool $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set the value of async
     *
     * @return  self
     */
    public function setAsync(bool $async)
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Set the value of data
     *
     * @return  self
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the value of code
     *
     * @return  self
     */ 
    public function setCode(int $code)
    {
        $this->code = $code;

        return $this;
    }
}
