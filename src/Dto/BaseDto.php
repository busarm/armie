<?php

namespace Busarm\PhpMini\Dto;

use Busarm\PhpMini\Helpers\Security;
use ReflectionObject;
use Busarm\PhpMini\Interfaces\Arrayable;
use Busarm\PhpMini\Interfaces\Attribute\PropertyAttributeInterface;
use Busarm\PhpMini\Traits\TypeResolver;
use InvalidArgumentException;
use ReflectionProperty;
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
     * Explicitly selected fields
     *
     * @var array<string>|null
     */
    private array|null $_selected = NULL;

    /**
     * Original properties
     *
     * @var array<string,mixed>|null
     */
    private array|null $_original = NULL;

    /** Default excluded fields */
    private const EXCLUDED_FILEDS = [
        '_selected', '_original'
    ];

    /**
     * Get dto properties
     *
     * @return ReflectionProperty[]
     */
    public function properties(): array
    {
        return (new ReflectionObject($this))->getProperties();
    }

    /**
     * Get dto field names & types
     *
     * @param bool $all Get all or only public field
     * @param bool $trim Get only initialized field
     * @return array<string,string> `[name => type]`. eg. `['id' => 'int']`
     */
    public function fields($all = true, $trim = false): array
    {
        $fields = [];
        foreach ($this->properties() as $property) {
            if (($all || $property->isPublic())
                && (!$trim || $property->isInitialized($this))
                && !in_array($property->getName(), self::EXCLUDED_FILEDS)
            ) {
                $type = $property->getType();
                if ($type) $fields[$property->getName()] = $this->getTypeName($type);
                else $fields[$property->getName()] = null;
            }
        }
        return $fields;
    }

    /**
     * Load data from array to class properties
     *
     * @param array $data
     * @param bool $sanitize
     * @return static
     */
    public function load(array $data, $sanitize = false): static
    {
        if ($sanitize)
            $data = Security::clean($data);

        if ($data) {
            foreach ($this->properties() as $property) {

                $name = $property->getName();

                if (!in_array($name, self::EXCLUDED_FILEDS)) {

                    $type = $this->getTypeName($property->getType());
                    $value = $property->getValue() ?? $this->{$name} ?? null;

                    if (array_key_exists($name, $data)) {
                        $value = $data[$name];
                        if ($type == self::class || is_subclass_of($type, self::class)) {
                            if (!is_array($value)) {
                                throw new InvalidArgumentException(sprintf("Value of '$name' in '%s' must be an array or object %s given", static::class, gettype($value)));
                            }
                            $value = $type::with($value);
                        } else if ($type == CollectionBaseDto::class || is_subclass_of($type, self::class)) {
                            if (!is_array($value)) {
                                throw new InvalidArgumentException(sprintf("Value of '$name' must be an array or object %s given", gettype($value)));
                            }
                            $value = $type::of($value);
                        } else {
                            $value = $this->resolveType($type ?: $this->findType($value), $value);
                        }
                    }

                    // Process attributes if available
                    $value = $this->processFieldAttributes($property, $value);

                    $this->{$name} = $value;
                }
            }
        }

        $this->_original = $this->fields(false);
        return $this;
    }

    /**
     * Get original properties
     *
     * @return array
     */
    public function original(): array
    {
        return $this->_original ?? [];
    }

    /**
     * Get explicitly selected fields
     *
     * @return array
     */
    public function selected(): array
    {
        return $this->_selected ?? [];
    }

    /**
     * Explicitly select fields
     *
     * @param array $fields
     * @return static
     */
    public function select(array $fields): static
    {
        $this->_selected = $fields;
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
     * @param bool $sanitize - Perform security cleaning
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
                if ($value instanceof CollectionBaseDto) {
                    $result[$attr] = $value->toArray($trim);
                } else if ($value instanceof self) {
                    $result[$attr] = $value->toArray($trim);
                } else if (is_array($value)) {
                    $result[$attr] = is_list($value) ? (CollectionBaseDto::of($value))->toArray($trim) : (self::with($value))->toArray($trim);
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
            }
        }
        return $sanitize ? Security::cleanParams($result) : $result;
    }

    /**
     * Process Field's Attributes
     *
     * @param ReflectionProperty $property
     * @param T|null $value
     * @return T|null
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
     * Load dto with array of class attibutes
     *
     * @param array|object|null $data
     * @param bool $sanitize
     * @return static|self
     */
    public static function with(array|object|null $data, $sanitize = false): self
    {
        $dto = new self;
        if ($data) {
            if ($data instanceof self) {
                $dto->load($data->toArray(), $sanitize);
            } else {
                $dto->load((array)$data, $sanitize);
            }
        }
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
