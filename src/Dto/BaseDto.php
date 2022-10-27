<?php

namespace Busarm\PhpMini\Dto;

use ReflectionNamedType;
use ReflectionObject;
use ReflectionUnionType;
use Busarm\PhpMini\Errors\DtoError;
use Busarm\PhpMini\Interfaces\Arrayable;
use ReflectionType;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class BaseDto implements Arrayable
{
    /**
     * Load data from array
     *
     * @param array $data
     * @param bool $force
     * @return self
     */
    public function load(array $data, $force = false): self
    {
        if ($data) {
            $reflectClass = new ReflectionObject($this);
            foreach ($reflectClass->getProperties() as $property) {
                if (isset($data[$property->getName()])) {
                    $this->{$property->getName()} = self::parseType($property->getType(), $data[$property->getName()]);
                } else if ($force && !$property->hasDefaultValue() && !$property->getType()->allowsNull()) {
                    throw new DtoError(sprintf("`%s` field cannot be null", $property->getName()));
                } else $this->{$property->getName()} = null;
            }
        }
        return $this;
    }

    /**
     * Get array response data
     * @param bool $trim Remove NULL properties
     * @return array
     */
    public function toArray($trim = true): array
    {
        $result = [];
        $reflectClass = new ReflectionObject($this);
        foreach ($reflectClass->getProperties() as $property) {
            if ((!$trim || isset($this->{$property->getName()})) && $property->isInitialized($this)) {
                $value = $this->{$property->getName()};
                if ($value instanceof CollectionBaseDto) {
                    $result[$property->getName()] = $value->toArray();
                } else if ($value instanceof self) {
                    $result[$property->getName()] = $value->toArray();
                } else if (is_array($value)) {
                    foreach ($value as &$data) {
                        if ($data instanceof CollectionBaseDto) {
                            $data = $data->toArray();
                        } else if ($data instanceof self) {
                            $data = $data->toArray();
                        } else {
                            $data = self::parseType(self::resolveType($data), $data);
                        }
                    }
                    $result[$property->getName()] = $value;
                } else $result[$property->getName()] = self::parseType(self::resolveType($value), $value);
            }
        }
        return $result;
    }

    /**
     * Parse object type
     *
     * @param ReflectionUnionType|ReflectionNamedType|ReflectionType|string $type
     * @param mixed $data
     * @return mixed
     */
    public static function parseType($type, $data)
    {
        if ($type instanceof ReflectionUnionType) {
            $type = self::resolveType($data, $type->getTypes());
        }
        if ($type instanceof ReflectionNamedType) {
            $type = $type->getName();
        }

        $type = strtolower((string)$type);

        if ($type == 'string') {
            $data = is_array($data) || is_object($data) ? json_encode($data) : (string) $data;
        } else if ($type == 'int' || $type == 'integer') {
            $data = intval($data);
        } else if ($type == 'bool' || $type == 'boolean') {
            $data = boolval($data);
        } else if ($type == 'float') {
            $data = floatval($data);
        } else if ($type == 'double') {
            $data = doubleval($data);
        } else if ($type == 'array') {
            $data = is_string($data) ? json_decode($data, true) : (array) $data;
        } else if ($type == 'object') {
            $data = is_string($data) ? json_decode($data) : (object) $data;
        }

        return $data;
    }

    /**
     * Resolve data type
     *
     * @param mixed $data
     * @param ReflectionNamedType[] $types
     * @return string
     */
    public static function resolveType($data, $types = [])
    {
        if (empty($types) || !in_array('null', $types)) {
            if (is_int($data) || is_numeric($data)) {
                if (in_array('bool', $types) || in_array('boolean', $types)) return 'bool';
                return 'int';
            } else if ($data === 'true' || $data === 'false' || is_bool($data)) return 'bool';
            else if (is_array($data)) return 'array';
            else if (is_object($data)) return 'object';
            else if (is_string($data)) return 'string';
        }
        return 'mixed';
    }

    /**
     * Load dto with array
     *
     * @param array|object|null $data
     * @return static
     */
    public static function with(array|object|null $data): static
    {
        $response = new self();
        if ($data) $response->load((array)$data);
        return $response;
    }
}
