<?php

namespace Armie\Data;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Helpers\Security;
use Armie\Interfaces\Arrayable;
use Armie\Interfaces\Attribute\PropertyAttributeInterface;
use Armie\Traits\TypeResolver;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use Stringable;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
abstract class DataObject implements Arrayable, Stringable, JsonSerializable
{
    use TypeResolver;

    /**
     * Explicitly selected fields.
     *
     * @var array<string>
     */
    protected array $_selected = [];

    /**
     * Update available.
     */
    protected bool $_isDirty = false;

    /**
     * Load attribute.
     */
    protected bool $_loadAttr = true;

    /**
     * Load property types.
     */
    protected bool $_loadTypes = true;

    /**
     * Get excluded fields from properties.
     */
    protected function __excluded(): array
    {
        return [
            '_selected', '_isDirty', '_loadAttr', '_loadTypes',
        ];
    }

    public function __sleep(): array
    {
        return array_merge(
            array_keys($this->fields()),
            $this->__excluded()
        );
    }

    public function __wakeup(): void
    {
    }

    public function __serialize(): array
    {
        $list = [];

        foreach ($this->__sleep() as $key) {
            $list[$key] = $this->__get($key);
        }

        return $list;
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }

        $this->__wakeup();
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $key = strip_tags(stripslashes($key));

        if (in_array($key, $this->__excluded())) {
            $this->{$key} = $value;

            return;
        }

        if ($this->__get($key) != $value) {
            $this->_isDirty = true;
        }

        // If class property
        if ($this->_loadTypes && property_exists($this, $key)) {
            $property = new ReflectionProperty($this, $key);

            // Resolve type of value
            $value = $this->resolvePropertyType($property, $value);

            if ($this->_loadAttr) {
                // Process attributes if available
                $value = $this->processFieldAttributes($property, $value);
                $this->{$key} = $value;
            }
        }
        // If custom property
        else {
            $this->{$key} = $this->resolveType($this->findType($value), $value);
        }
    }

    /**
     * @param mixed $key
     */
    public function __get($key)
    {
        return $this->{strip_tags(stripslashes($key))} ?? null;
    }

    /**
     * Get properties.
     *
     * @param bool $all  Get all or only public field
     *
     * @return ReflectionProperty[]
     */
    public function properties($all = false): array
    {
        return (new ReflectionClass($this))->getProperties(
            $all ?
                ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_READONLY | ReflectionProperty::IS_PUBLIC :
                ReflectionProperty::IS_PUBLIC
        );
    }

    /**
     * Get field names & types.
     *
     * @param bool $all  Get all or only public field
     * @param bool $trim Get only initialized field
     *
     * @return array<string,string> `[name => type]`. eg. `['id' => 'int']`
     */
    public function fields($all = true, $trim = false): array
    {
        $fields = [];
        $excluded = $this->__excluded();
        foreach ($this->properties($all) as $property) {
            if (
                !$property->isStatic()
                && (!$trim || $property->isInitialized($this))
                && !in_array($property->getName(), $excluded)
                && !str_starts_with($property->getName(), '_')
            ) {
                $type = $property->getType();
                if ($type) {
                    $fields[$property->getName()] = $this->getTypeName($type);
                } else {
                    $fields[$property->getName()] = null;
                }
            }
        }

        return $fields;
    }

    /**
     * Quickly load data from array to class properties - Without processing property types and attributes.
     *
     * @param array $data
     * @param bool  $sanitize
     *
     * @return static
     */
    public function fastLoad(array $data, $sanitize = false): static
    {
        if ($sanitize) {
            $data = Security::clean($data);
        }

        if ($data) {
            $this->_loadAttr = false;
            $this->_loadTypes = false;
            foreach ($data as $name => $value) {
                $this->{$name} = $value;
            }
        }

        return $this;
    }

    /**
     * Load data from array to class properties.
     *
     * @param array $data
     * @param bool  $sanitize
     *
     * @return static
     */
    public function load(array $data, $sanitize = false): static
    {
        if ($sanitize) {
            $data = Security::clean($data);
        }

        if ($data) {
            foreach ($data as $name => $value) {
                $this->{$name} = $value;
            }
        }

        return $this;
    }

    /**
     * Is Dirty - Update has been made.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->_isDirty;
    }

    /**
     * Get explicitly selected fields.
     *
     * @return array
     */
    public function selected(): array
    {
        return $this->_selected ?? [];
    }

    /**
     * Explicitly select fields.
     *
     * @param array $fields
     *
     * @return static
     */
    public function select(array $fields): static
    {
        $this->_selected = $fields;

        return $this;
    }

    /**
     * Get property.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->__get($key) ?? $default;
    }

    /**
     * Set property.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set(string $key, mixed $value = null): void
    {
        $this->__set($key, $value);
    }

    /**
     * Convert to array.
     *
     * @param bool $trim     - Remove NULL properties
     * @param bool $sanitize - Perform security cleaning
     *
     * @return array
     */
    public function toArray($trim = true, $sanitize = false): array
    {
        $result = [];
        foreach ($this->fields() as $attr => $type) {
            if (
                (empty($this->_selected) || in_array('*', $this->_selected) || in_array($attr, $this->_selected))
                && property_exists($this, $attr)
                && (!$trim || isset($this->{$attr}))
            ) {
                $value = $this->{$attr} ?? null;
                if ($value !== null) {
                    if ($value instanceof self) {
                        $result[$attr] = $value->toArray($trim, $sanitize);
                    } elseif ($value instanceof CollectionBaseDto) {
                        $result[$attr] = $value->toArray($trim, $sanitize);
                    } elseif ($value instanceof Arrayable) {
                        $result[$attr] = $value->toArray($trim);
                    } elseif (is_array($value)) {
                        $result[$attr] = array_is_list($value) ? (CollectionBaseDto::of($value))->toArray($trim, $sanitize) : (BaseDto::with($value))->toArray($trim, $sanitize);
                    } else {
                        $value = $this->resolveType($type ?
                            $this->getTypeName($type) :
                            $this->findType($value), $value);
                        if ($value instanceof Stringable) {
                            $result[$attr] = strval($value);
                        } else {
                            $result[$attr] = $value;
                        }
                    }
                } else {
                    $result[$attr] = $value;
                }
            }
        }

        return $sanitize ? Security::cleanParams($result) : $result;
    }

    /**
     * Process Field's Attributes.
     *
     * @param ReflectionProperty $property
     * @param T|null             $value
     *
     * @return T|null
     *
     * @template T
     */
    protected function processFieldAttributes(ReflectionProperty $property, mixed $value = null)
    {
        $result = $value;
        foreach ($property->getAttributes() as $field) {
            $instance = $field->newInstance();
            if ($instance instanceof PropertyAttributeInterface) {
                $result = $instance->processProperty($property, $value);
            }
        }

        return $result;
    }

    /**
     * Gets a string representation of the object.
     *
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * Specify data which should be serialized to JSON
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return mixed Returns data which can be serialized by json_encode(), which is a value of any type other than a resource .
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
