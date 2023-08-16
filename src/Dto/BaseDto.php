<?php

namespace Armie\Dto;

use Armie\Helpers\Security;
use ReflectionObject;
use Armie\Interfaces\Arrayable;
use Armie\Traits\PropertyLoader;
use Armie\Traits\TypeResolver;
use Stringable;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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
