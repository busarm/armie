<?php

namespace Armie\Data\PDO;

use Armie\Enums\DataType;
use Armie\Interfaces\Data\FieldInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Field implements FieldInterface
{
    /**
     * @param string   $name Field name
     * @param DataType $type Field type
     */
    public function __construct(private string $name, private DataType $type)
    {
    }

    /**
     * Get field name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get field type.
     *
     * @return DataType
     */
    public function getType(): DataType
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
