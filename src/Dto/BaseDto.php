<?php

namespace Armie\Dto;

use Armie\Data\DataObject;
use Armie\Interfaces\Arrayable;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class BaseDto extends DataObject
{
    /**
     * Load dto with array of class attibutes.
     *
     * @param Arrayable|self|array|null $data
     * @param bool                      $sanitize
     *
     * @return self
     */
    public static function with(Arrayable|self|array|null $data, $sanitize = false): self
    {
        $dto = new self();
        if ($data) {
            if ($data instanceof self || $data instanceof parent || $data instanceof Arrayable) {
                $dto->load($data->toArray(), $sanitize);
            } else {
                $dto->load((array) $data, $sanitize);
            }
        }

        return $dto;
    }
}
