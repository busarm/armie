<?php

namespace Busarm\PhpMini\Dto;

use ReflectionObject;
use Busarm\PhpMini\Interfaces\Arrayable;
use Busarm\PhpMini\Traits\TypeResolver;
use InvalidArgumentException;
use Stringable;

use function Busarm\PhpMini\Helpers\is_list;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class BaseDto implements Arrayable, Stringable
{
    use TypeResolver;

    /**
     * Explicitly selected attributes
     *
     * @var array
     */
    protected array $selectedAttrs = [];

    /**
     * Get dto attribute names & types
     *
     * @param bool $all - Get all or only public attributes
     * @param bool $trim - Get only initialized attributes
     * @return array<string,string> - `[name => type]`. eg. `['id' => 'int']`
     */
    public function attributes($all = true, $trim = false): array
    {
        $attributes = [];
        $reflectClass = new ReflectionObject($this);
        foreach ($reflectClass->getProperties() as $property) {
            if ($all || $property->isPublic() && (!$trim || $property->isInitialized($this))) {
                $type = $property->getType();
                if ($type) {
                    if ($type instanceof \ReflectionUnionType) {
                        $attributes[$property->getName()] =  ($type->getTypes()[0])?->getName();
                    } else if (
                        $type instanceof \ReflectionNamedType
                    ) {
                        $attributes[$property->getName()] = $type->getName();
                    } else {
                        $attributes[$property->getName()] = strval($type);
                    }
                } else $attributes[$property->getName()] = null;
            }
        }
        return $attributes;
    }

    /**
     * Load data from array with class attributes
     *
     * @param array $data
     * @return static
     */
    public function load(array $data): static
    {
        if ($data) {
            foreach ($this->attributes() as $attr => $type) {
                if (array_key_exists($attr, $data)) {
                    if ($type == self::class) {
                        if (!is_array($data[$attr])) {
                            throw new InvalidArgumentException("$attr must be an array or object");
                        }
                        $this->{$attr} = self::with($data[$attr]);
                    } else if ($type == CollectionBaseDto::class) {
                        if (!is_array($data[$attr])) {
                            throw new InvalidArgumentException("$attr must be an array");
                        }
                        $this->{$attr} = CollectionBaseDto::of($data[$attr]);
                    } else {
                        $this->{$attr} = $this->resolveType($type ?: $this->findType($data[$attr]), $data[$attr]);
                    }
                } else if (!isset($this->{$attr})) {
                    $this->{$attr} = null;
                }
            }
        }
        return $this;
    }

    /**
     * Load data from array with custom values
     *
     * @param array $data
     * @return self
     */
    public function loadCustom(array $data): self
    {
        if ($data) {
            $attibutes = $this->attributes();
            $attibutesKeys = array_keys($this->attributes());
            foreach ($data as $key => $value) {
                if (empty($attibutesKeys) || in_array($key, $attibutesKeys)) {
                    $this->{$key} = $this->resolveType($attibutes[$key] ?? $this->findType($value), $value);
                }
            }
        }
        return $this;
    }

    /**
     * Explicitly select attributes
     *
     * @param array $attributes
     * @return static
     */
    public function select(array $attributes): static
    {
        $this->selectedAttrs = $attributes;
        return $this;
    }

    /**
     * Get property
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return isset($this->{$key}) ? $this->{$key} : $default;
    }

    /**
     * Set property
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set(string $key, mixed $value = null): mixed
    {
        return $this->{$key} = $value;
    }

    /**
     * Convert dto to array
     * 
     * @param bool $trim - Remove NULL properties
     * @return array
     */
    public function toArray($trim = true): array
    {
        $result = [];
        foreach ($this->attributes() as $attr => $type) {
            if (
                (empty($this->selectedAttrs) || in_array('*', $this->selectedAttrs) || in_array($attr, $this->selectedAttrs)) &&
                property_exists($this, $attr) &&
                (!$trim || isset($this->{$attr}))
            ) {
                $value = $this->{$attr} ?? null;
                if ($value instanceof CollectionBaseDto) {
                    $result[$attr] = $value->toArray($trim);
                } else if ($value instanceof self) {
                    $result[$attr] = $value->toArray($trim);
                } else if (is_array($value)) {
                    $result[$attr] = is_list($value) ? (CollectionBaseDto::of($value, static::class))->toArray($trim) : (self::with($value))->toArray($trim);
                } else {
                    $value = $this->resolveType($type ?: $this->findType($value), $value);
                    if ($value instanceof Stringable) {
                        $result[$attr] = strval($value);
                    } else {
                        $result[$attr] = $value;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Load dto with array of class attibutes
     *
     * @param array|object|null $data
     * @return static|self
     */
    public static function with(array|object|null $data): self
    {
        $dto = new self;
        if ($data) {
            if ($data instanceof self) {
                $dto->load($data->toArray());
            } else {
                $dto->load((array)$data);
            }
        }
        return $dto;
    }

    /**
     * Load dto with array of custom data
     *
     * @param array|object|null $data
     * @return static|self
     */
    public static function withCustom(array|object|null $data): self
    {
        $dto = new self;
        if ($data) $dto->loadCustom((array)$data);
        return $dto;
    }

    /**
     * Gets a string representation of the object
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
