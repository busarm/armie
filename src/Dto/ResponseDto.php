<?php

namespace Busarm\PhpMini\Dto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ResponseDto extends BaseDto
{
    /**  @var bool */
    public bool $success;
    /** @var string */
    public string|null $message;
    /** @var object|array */
    public object|array|null $data;
    /** @var string */
    public string|null $env;
    /** @var string */
    public string|null $ip;
    /** @var string */
    public string|null $version;
    /** @var string */
    public string|null $code;
    /** @var string */
    public string|null $line;
    /** @var string */
    public string|null $file;
    /** @var array */
    public array|null $backtrace;
    /** @var int */
    public int|null $duration;
}
