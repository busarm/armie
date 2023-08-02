<?php

namespace Busarm\PhpMini\Traits;

use ArrayObject;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Enums\DataType;
use Busarm\PhpMini\Helpers\StringableDateTime;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use ReflectionNamedType;
use ReflectionUnionType;

/**  
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
trait TypeResolver
{
    /**
     * Resolve data type
     *
     * @param \ReflectionProperty $property
     * @param mixed $data
     * @return mixed - Variable with appropraite type
     */
    protected function resolvePropertyType($property, $data)
    {
        if ($data !== null) {

            $name = $property->getName();
            $type = strval($property->getType());

            if ($type == BaseDto::class || is_subclass_of($type, BaseDto::class)) {
                if (!is_array($data)) {
                    throw new InvalidArgumentException(sprintf("Value of '$name' in '%s' must be an array or object %s given", static::class, gettype($data)));
                }
                $data = $type::with($data);
            } else if ($type == CollectionBaseDto::class || is_subclass_of($type, CollectionBaseDto::class)) {
                if (!is_array($data)) {
                    throw new InvalidArgumentException(sprintf("Value of '$name' must be an array or object %s given", gettype($data)));
                }
                $data = $type::of($data);
            } else {
                $data = $this->resolveType($property->getType(), $data);
            }

            return $data;
        }

        return null;
    }

    /**
     * Resolve data type
     *
     * @param ReflectionUnionType|ReflectionNamedType|\ReflectionType|string $type
     * @param mixed $data
     * @return mixed - Variable with appropraite type
     */
    protected function resolveType($type, $data)
    {
        $type = $this->getTypeName($type, $data);

        if ($data !== null) {
            return match ($type) {
                DataType::INT, DataType::INTEGER => intval($data),
                DataType::BOOL, DataType::BOOLEAN => boolval($data),
                DataType::FLOAT => floatval($data),
                DataType::DOUBLE => doubleval($data),
                DataType::ARRAY => is_string($data) ? ($this->resolveArrayJson($data) || $this->resolveArrayCSV($data) || $this->resolveArraySSV($data)) : ((array) $data),
                DataType::OBJECT, DataType::JSON => is_string($data) ? json_decode($data) : (object) $data,
                DataType::STRING => is_array($data) || is_object($data) ? json_encode($data) : strval($data),
                DataType::DATETIME, StringableDateTime::class, DateTime::class, DateTimeImmutable::class, DateTimeInterface::class => $data instanceof DateTimeInterface ?
                    strval(StringableDateTime::createFromInterface($data)) :
                    strval(new StringableDateTime($data, new DateTimeZone(date_default_timezone_get()))),
                ArrayObject::class => new ArrayObject(is_string($data) ? json_decode($data, true) : (array) $data),
                default => $data
            };
        }

        return null;
    }

    /**
     * Identify the data type by analyzing the data
     *
     * @param mixed $data
     * @param ReflectionNamedType[] $types
     * @return \Busarm\PhpMini\Enums\DataType::*|string
     */
    protected function findType($data, $types = [])
    {
        $types = array_map(fn ($type) => strval($type), $types);
        if ($data !== null) {
            return match (true) {
                is_numeric($data) && is_bool($data) && (in_array(DataType::BOOL, $types) || in_array(DataType::BOOLEAN, $types)) => DataType::BOOL,
                is_numeric($data) && is_double($data) && in_array(DataType::DOUBLE, $types) => DataType::DOUBLE,
                is_numeric($data) && is_double($data) && in_array(DataType::FLOAT, $types) => DataType::FLOAT,
                is_numeric($data) && (is_integer($data) || is_int($data)) => DataType::INT,
                is_bool($data) || $data === 'true' || $data === 'false' => DataType::BOOL,
                is_double($data) => DataType::DOUBLE,
                is_float($data) => DataType::FLOAT,
                is_array($data) => DataType::ARRAY,
                is_object($data) => DataType::OBJECT,
                is_string($data) => $this->isDate($data) ? DataType::DATETIME : DataType::STRING,
            };
        }
        return $types[0] ?? DataType::MIXED;
    }

    /**
     * Get type name
     *
     * @param ReflectionUnionType|ReflectionNamedType|\ReflectionType|string $type
     * @param mixed $data
     * @return string 
     */
    protected function getTypeName($type, $data = null)
    {
        if ($type instanceof ReflectionUnionType) {
            $type = $this->findType($data, $type->getTypes());
        }
        if ($type instanceof ReflectionNamedType) {
            $type = $type->getName();
        }

        return strval($type);
    }

    /**
     * Check if string is a date
     *
     * @param string $str
     * @return bool
     */
    private function isDate($str)
    {
        return preg_match("(:|-|\/)", $str) && strtotime($str);
    }

    /**
     * Resolve json data
     *
     * @param string $data
     * @return array|null
     */
    private function resolveArrayJson($data)
    {
        $result = $data ? json_decode($data, true) : null;
        return !empty($result) ? $result : null;
    }

    /**
     * Resolve comma separated data
     *
     * @param string $data
     * @return array|null
     */
    private function resolveArrayCSV($data)
    {
        $result = $data ? explode(",", $data) : null;
        return !empty($result) ? $result : null;
    }

    /**
     * Resolve space separated data
     *
     * @param string $data
     * @return array|null
     */
    private function resolveArraySSV($data)
    {
        $result = $data ? explode(" ", $data) : null;
        return !empty($result) ? $result : null;
    }
}
