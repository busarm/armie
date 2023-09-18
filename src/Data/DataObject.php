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
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
abstract class DataObject implements Arrayable, Stringable, JsonSerializable
{
    use TypeResolver;

    /**
     * Loaded properties.
     *
     * @var array<string, mixed>
     */
    protected array $_props = [];

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
     * Get excluded fields from properties.
     * 
     * Override to exclude custom values
     */
    protected function __excluded(): array
    {
        return [
            '_props', '_selected', '_isDirty', '_loadAttr'
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
        $this->set($key, $value);
    }

    /**
     * @param mixed $key
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Set Is Dirty - Update has been made.
     *
     * @param boolean $isDirty
     * @return static
     */
    protected function setIsDirty(bool $isDirty): static
    {
        $this->_isDirty = $isDirty;

        return $this;
    }

    /**
     * Is Dirty - Update has been made.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->_isDirty || !empty(array_intersect(array_keys($this->fields()), array_keys($this->_props)));
    }

    /**
     * Get properties.
     *
     * @param bool $all Get all or only public field
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
            ) {
                $type = $property->getType();
                if ($type) {
                    $fields[$property->getName()] = $this->getTypeName($type);
                } else {
                    $fields[$property->getName()] = null;
                }
            }
        }

        return [...$fields, ...array_map(fn ($item) => $this->findType($item), $this->_props)];
    }

    /**
     * Quickly load data from array to class properties - Without processing attributes.
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
            foreach ($data as $name => $value) {
                $this->set($name, $value);
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
                $this->set($name, $value);
            }
        }

        return $this;
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
        return property_exists($this, $key)
            ? ($this->{$key} ?? $default)
            : ($this->_props[strip_tags(stripslashes($key))] ?? $default);
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
        $key = strip_tags(stripslashes($key));

        if (in_array($key, $this->__excluded())) {
            if (property_exists($this, $key)) $this->{$key} = $value;
            else $this->_props[$key] = $value;

            return;
        }

        if ($this->get($key) != $value) {
            $this->setIsDirty(true);
        }

        if (property_exists($this, $key)) {
            // Process types
            $property = new ReflectionProperty($this, $key);
            $value = $this->resolvePropertyType($property, $value);
            // Process attributes 
            if ($this->_loadAttr) {
                $value = $this->processFieldAttributes($property, $value);
            }
            $this->{$key} = $value;
        } else {
            $value = $this->resolveType($this->findType($value), $value);
            $this->_props[$key] = $value;
        }
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
        foreach ($this->fields() as $key => $type) {
            if (
                (empty($this->_selected) || in_array('*', $this->_selected) || in_array($key, $this->_selected))
            ) {
                $value = $this->get($key);
                if ($value !== null) {
                    if ($value instanceof self) {
                        $result[$key] = $value->toArray($trim, $sanitize);
                    } elseif ($value instanceof CollectionBaseDto) {
                        $result[$key] = $value->toArray($trim, $sanitize);
                    } elseif ($value instanceof Arrayable) {
                        $result[$key] = $value->toArray($trim);
                    } elseif (is_array($value)) {
                        $result[$key] = array_is_list($value) ? (CollectionBaseDto::of($value))->toArray($trim, $sanitize) : (BaseDto::with($value))->toArray($trim, $sanitize);
                    } elseif (is_iterable($value)) {
                        $result[$key] = (CollectionBaseDto::of($value))->toArray($trim, $sanitize);
                    } else {
                        $value = $this->resolveType(!empty($type) ? $this->getTypeName($type) : $this->findType($value), $value);
                        if ($value instanceof Stringable) {
                            $result[$key] = strval($value);
                        } else {
                            $result[$key] = $value;
                        }
                    }
                } else if (!$trim) {
                    $result[$key] = $value;
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
