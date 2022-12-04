<?php

namespace Busarm\PhpMini\Data\PDO;

use Stringable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Field implements Stringable
{
    /**
     * @param string $name Field name
     * @param string $type Field type @see \Busarm\PhpMini\Enums\DataType::class
     */
    public function __construct(private string $name, private string $type)
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
     */
    public function getType()
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
