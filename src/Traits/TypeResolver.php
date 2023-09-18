<?php

namespace Armie\Traits;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Enums\DataType;
use Armie\Helpers\StringableDateTime;
use ArrayObject;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
trait TypeResolver
{
    /**
     * Resolve data type.
     *
     * @param \ReflectionProperty $property
     * @param mixed               $data
     *
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
            } elseif ($type == CollectionBaseDto::class || is_subclass_of($type, CollectionBaseDto::class)) {
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
     * Resolve data type.
     *
     * @param ReflectionUnionType|ReflectionNamedType|\ReflectionType|DataType|string $type
     * @param mixed                                                                   $data
     *
     * @return mixed - Variable with appropraite type
     */
    protected function resolveType($type, $data)
    {
        $type = $this->getTypeName($type instanceof DataType ? $type->value : $type, $data);

        if ($data !== null) {
            return match ($type) {
                DataType::INT->value, DataType::INTEGER->value => intval($data),
                DataType::BOOL->value, DataType::BOOLEAN->value => boolval($data),
                DataType::FLOAT->value  => floatval($data),
                DataType::ARRAY->value  => is_string($data) ? ($this->resolveArrayJson($data) ?? $this->resolveArrayCSV($data) ?? $this->resolveArraySSV($data)) : ((array) $data),
                DataType::OBJECT->value, DataType::JSON->value => is_string($data) ? json_decode($data) : (object) $data,
                DataType::STRING->value => is_array($data) || is_object($data) ? json_encode($data) : strval($data),
                DataType::DATETIME->value => $data instanceof DateTimeInterface ?
                    $data->format(DateTime::W3C) :
                    strval(new StringableDateTime($data)),
                StringableDateTime::class, DateTime::class, DateTimeImmutable::class, DateTimeInterface::class => $data instanceof DateTimeInterface ?
                    $data :
                    new StringableDateTime($data),
                ArrayObject::class => new ArrayObject($this->resolveType(DataType::ARRAY, $data) ?? []),
                default            => $data
            };
        }

        return null;
    }

    /**
     * Identify the data type by analyzing the data.
     *
     * @param mixed                 $data
     * @param ReflectionNamedType[] $types
     *
     * @return DataType|string
     */
    protected function findType($data, $types = [])
    {
        $types = array_map(fn ($type) => strval($type), $types);
        if ($data !== null) {
            return match (true) {
                is_numeric($data)
                    && (in_array(DataType::BOOL->value, $types) || in_array(DataType::BOOLEAN->value, $types)) => DataType::BOOL,
                is_numeric($data)
                    && is_float($data)
                    && in_array(DataType::FLOAT->value, $types)    => DataType::FLOAT->value,
                is_bool($data) || $data === 'true' || $data === 'false' => DataType::BOOL->value,
                is_float($data)     => DataType::FLOAT->value,
                is_int($data)       => DataType::INT->value,
                is_array($data)     => DataType::ARRAY->value,
                is_object($data)    => DataType::OBJECT->value,
                is_string($data)    => $this->isDate($data) ? DataType::DATETIME->value : DataType::STRING->value,
                default             => false
            };
        }

        return $types[0] ?? DataType::MIXED->value;
    }

    /**
     * Get type name.
     *
     * @param ReflectionUnionType|ReflectionNamedType|\ReflectionType|string $type
     * @param mixed                                                          $data
     *
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
     * Check if string is a date.
     *
     * @param string $str
     *
     * @return bool
     */
    private function isDate($str)
    {
        return preg_match("(:|-|\/)", $str) && strtotime($str);
    }

    /**
     * Resolve json data.
     *
     * @param string $data
     *
     * @return array|null
     */
    private function resolveArrayJson($data)
    {
        $result = $data ? json_decode($data, true) : null;

        return !empty($result) ? $result : null;
    }

    /**
     * Resolve comma separated data.
     *
     * @param string $data
     *
     * @return array|null
     */
    private function resolveArrayCSV($data)
    {
        $result = $data ? explode(',', $data) : null;

        return !empty($result) ? $result : null;
    }

    /**
     * Resolve space separated data.
     *
     * @param string $data
     *
     * @return array|null
     */
    private function resolveArraySSV($data)
    {
        $result = $data ? explode(' ', $data) : null;

        return !empty($result) ? $result : null;
    }
}
