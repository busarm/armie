<?php

namespace Busarm\PhpMini\Traits;

use ArrayObject;
use Busarm\PhpMini\Enums\DataType;
use Busarm\PhpMini\Helpers\StringableDateTime;
use DateTime;
use DateTimeInterface;
use ReflectionNamedType;
use ReflectionUnionType;


/**
 * Manage Singletons
 *  
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
trait TypeResolver
{
    /**
     * Resolve variable type
     *
     * @param ReflectionUnionType|ReflectionNamedType|\ReflectionType|string $type
     * @param mixed $data
     * @return mixed - Variable with appropraite type
     */
    public function resolveType($type, $data)
    {
        $type = $this->getTypeName($type, $data);

        if ($data !== null) {
            return match ($type) {
                DataType::INT, DataType::INTEGER => intval($data),
                DataType::BOOL, DataType::BOOLEAN => boolval($data),
                DataType::FLOAT => floatval($data),
                DataType::DOUBLE => doubleval($data),
                DataType::ARRAY => is_string($data) ? json_decode($data, true) : (array) $data,
                DataType::OBJECT, DataType::JSON => is_string($data) ? json_decode($data) : (object) $data,
                DataType::STRING => is_array($data) || is_object($data) ? json_encode($data) : strval($data),
                ArrayObject::class => new ArrayObject(is_string($data) ? json_decode($data, true) : (array) $data),
                StringableDateTime::class, DateTime::class, DateTimeInterface::class, DataType::DATETIME =>  is_string($data) ? (new StringableDateTime($data)) : StringableDateTime::createFromInterface($data),
                default => $data
            };
        }

        return match ($type) {
            DataType::BOOL, DataType::BOOLEAN => false,
            DataType::ARRAY => [],
            ArrayObject::class => new ArrayObject([]),
            default => $data
        };
    }

    /**
     * Identify the data type by analyzing the data
     *
     * @param mixed $data
     * @param ReflectionNamedType[] $types
     * @return string
     */
    public function findType($data, $types = [])
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
                is_string($data) => $this->checkDate($data) ? DataType::DATETIME : DataType::STRING,
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
    private function getTypeName($type, $data)
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
     * Validate and resolve date
     *
     * @param string $str
     * @return DateTime|bool
     */
    public function validateDate($str)
    {
        // Contains datetime separators
        if (!$this->checkDate($str)) return false;

        $formats = [
            DateTime::ATOM,
            DateTime::COOKIE,
            DateTime::ISO8601,
            DateTime::RFC1036,
            DateTime::RFC1123,
            DateTime::RFC2822,
            DateTime::RFC3339,
            DateTime::RFC3339_EXTENDED,
            DateTime::RFC7231,
            DateTime::RFC822,
            DateTime::RFC850,
            DateTime::RSS,
            DateTime::W3C,
        ];
        foreach ($formats as $format) {
            if ($date = DateTime::createFromFormat($format, $str)) {
                return $date;
            }
        }
        return false;
    }

    /**
     * Check if string is a data
     *
     * @param string $str
     * @return bool
     */
    private function checkDate($str)
    {
        return !preg_match("(:|-|\/)", $str) && strtotime($str);
    }
}
