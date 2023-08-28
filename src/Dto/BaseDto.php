<?php

namespace Armie\Dto;

use Armie\Interfaces\Data\PropertyResolverInterface;
use Armie\Traits\PropertyResolver;
use Armie\Traits\TypeResolver;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class BaseDto implements PropertyResolverInterface
{
    use TypeResolver;
    use PropertyResolver;

    /**
     * Load dto with array of class attibutes.
     *
     * @param array|object|null $data
     * @param bool              $sanitize
     *
     * @return self
     */
    public static function with(array|object|null $data, $sanitize = false): self
    {
        $dto = new self();
        if ($data) {
            if ($data instanceof self) {
                $dto->load($data->toArray(), $sanitize);
            } else {
                $dto->load((array) $data, $sanitize);
            }
        }

        return $dto;
    }
}
