<?php

namespace Busarm\PhpMini\Dto;

use Busarm\PhpMini\Helpers\Security;
use ReflectionObject;
use Busarm\PhpMini\Interfaces\Arrayable;
use Busarm\PhpMini\Interfaces\Attribute\PropertyAttributeInterface;
use Busarm\PhpMini\Traits\PropertyLoader;
use Busarm\PhpMini\Traits\TypeResolver;
use ReflectionProperty;
use Stringable;

use function Busarm\PhpMini\Helpers\is_list;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class BaseDto implements Arrayable, Stringable
{
    use TypeResolver;
    use PropertyLoader;

    /**
     * Load dto with array of class attibutes
     *
     * @param array|object|null $data
     * @param bool $sanitize
     * @return self
     */
    public static function with(array|object|null $data, $sanitize = false): self
    {
        $dto = new self;
        if ($data) {
            if ($data instanceof self) {
                $dto->load($data->toArray(), $sanitize);
            } else {
                $dto->load((array)$data, $sanitize);
            }
        }
        return $dto;
    }
}
