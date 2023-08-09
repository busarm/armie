<?php

namespace Busarm\PhpMini\Data\PDO;

use Busarm\PhpMini\Enums\DataType;
use Busarm\PhpMini\Interfaces\Data\FieldInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Field implements FieldInterface
{
    /**
     * @param string $name Field name
     * @param DataType $type Field type
     */
    public function __construct(private string $name, private DataType $type)
    {
    }

    /**
     * Get field name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get field type
     * 
     * @return string* 
     */
    public function getType(): string
    {
        return $this->type->value;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
