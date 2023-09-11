<?php

namespace Armie\Dto;

use Armie\Data\DataObject;
use Armie\Helpers\Security;
use Armie\Interfaces\Arrayable;
use Armie\Traits\TypeResolver;
use ArrayObject;
use Generator;
use InvalidArgumentException;
use JsonSerializable;
use OutOfRangeException;
use Stringable;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @see https://stackoverflow.com/a/54096881
 *
 * @template T Item type template
 */
class CollectionBaseDto extends ArrayObject implements Arrayable, Stringable, JsonSerializable
{
    use TypeResolver;

    /**
     * @param iterable<T>      $list
     * @param ?class-string<T> $itemClass Define the class that will be used for all items in the array.
     *
     * @throws InvalidArgumentException
     */
    final private function __construct(iterable $list = [], private ?string $itemClass = null)
    {
        parent::__construct(iterator_to_array($this->validate($list)));
    }

    /**
     * Load data.
     *
     * @param iterable<T> $list
     * @param bool        $sanitize
     *
     * @return static
     */
    public function load(iterable $list, $sanitize = false): static
    {
        $this->exchangeArray(iterator_to_array($this->validate($list, $sanitize)));

        return $this;
    }

    /**
     * Validata data.
     *
     * @param iterable<T> $list
     * @param bool        $sanitize
     *
     * @return Generator
     */
    protected function validate(iterable $list, $sanitize = false): Generator
    {
        foreach ($list as $value) {
            if ($value !== null) {
                // Validate Item type if available
                if (!empty($this->itemClass)) {
                    if (is_subclass_of($this->itemClass, BaseDto::class) && !($value instanceof BaseDto) && is_array($value)) {
                        $value = $this->itemClass::with($value);
                    }
                    if (!($value instanceof ($this->itemClass))) {
                        throw new InvalidArgumentException(sprintf('Items of $list must be an instance of "%s", "%s" given.', $this->itemClass, get_class($value) ?: gettype($value)));
                    }
                }
            }

            yield $sanitize && $value !== null ? Security::clean($value) : $value;
        }

        return null;
    }

    /**
     * Clone a collection by cloning all items.
     */
    public function __clone()
    {
        foreach ($this as $key => $value) {
            $this[$key] = is_object($value) ? clone $value : $value;
        }
    }

    /**
     * Inserting the provided element at the index. If index is negative, it will be calculated from the end of the Array Object.
     *
     * @param int   $index
     * @param mixed $element
     */
    public function insert(int $index, $element)
    {
        $data = $this->getArrayCopy();
        if ($index < 0) {
            $index = $this->count() + $index;
        }

        $data = array_merge(array_slice($data, 0, $index, true), [$element], array_slice($data, $index, null, true));
        $this->exchangeArray($data);
    }

    /**
     * Remove a portion of the array and optionally replace it with something else.
     *
     * @see array_splice()
     *
     * @param int      $offset
     * @param int|null $length
     * @param mixed    $replacement
     *
     * @return static
     */
    public function splice(int $offset, int $length = null, $replacement = null)
    {
        $data = $this->getArrayCopy();

        // A null $length AND a null $replacement is not the same as supplying null to the call.
        if (is_null($length) && is_null($replacement)) {
            $result = array_splice($data, $offset);
        } else {
            $result = array_splice($data, $offset, $length, $replacement);
        }
        $this->exchangeArray($data);

        return new static($result);
    }

    /**
     * Adding a new value at the beginning of the collection.
     *
     * @param mixed $value
     *
     * @return int Returns the new number of elements in the Array
     */
    public function unshift($value): int
    {
        $data = $this->getArrayCopy();
        $result = array_unshift($data, $value);
        $this->exchangeArray($data);

        return $result;
    }

    /**
     * Extract a slice of the array.
     *
     * @see array_slice()
     *
     * @param int      $offset
     * @param int|null $length
     * @param bool     $preserveKeys
     *
     * @return static
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = false)
    {
        return new static(array_slice($this->getArrayCopy(), $offset, $length, $preserveKeys));
    }

    /**
     * Sort an array.
     *
     * @see sort()
     *
     * @param int $sortFlags
     *
     * @return bool
     */
    public function sort($sortFlags = SORT_REGULAR)
    {
        $data = $this->getArrayCopy();
        $result = sort($data, $sortFlags);
        $this->exchangeArray($data);

        return $result;
    }

    /**
     * Apply a user supplied function to every member of an array.
     *
     * @see array_walk
     *
     * @param callable   $callback
     * @param mixed|null $userData
     *
     * @return bool Returns true on success, otherwise false
     *
     * @see array_walk()
     */
    public function walk($callback, $userData = null)
    {
        $data = $this->getArrayCopy();
        $result = array_walk($data, $callback, $userData);
        $this->exchangeArray($data);

        return $result;
    }

    /**
     * Chunks the object into ArrayObject containing.
     *
     * @param int  $size
     * @param bool $preserveKeys
     *
     * @return ArrayObject
     */
    public function chunk(int $size, bool $preserveKeys = false): ArrayObject
    {
        $data = $this->getArrayCopy();
        $result = array_chunk($data, $size, $preserveKeys);

        return new ArrayObject($result);
    }

    /**
     * @see array_column
     *
     * @param mixed $columnKey
     *
     * @return array
     */
    public function column($columnKey): array
    {
        $data = $this->getArrayCopy();
        $result = array_column($data, $columnKey);

        return $result;
    }

    /**
     * @param callable $mapper Will be called as $mapper(mixed $item)
     *
     * @return static A collection of the results of $mapper(mixed $item)
     */
    public function map(callable $mapper): ArrayObject
    {
        $data = $this->getArrayCopy();
        $result = array_map($mapper, $data);

        return new static($result, $this->itemClass);
    }

    /**
     * Applies the callback function $callable to each item in the collection.
     *
     * @param callable $callable
     */
    public function each(callable $callable)
    {
        foreach ($this as &$item) {
            $callable($item);
        }
        unset($item);
    }

    /**
     * Returns the item in the collection at $index.
     *
     * @param int $index
     *
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     *
     * @return T
     */
    public function at(int $index)
    {
        $this->validateIndex($index);

        return $this[$index];
    }

    /**
     * Returns values in items of $key.
     *
     * @param string $key
     *
     * @return array
     */
    public function pluck(string $key)
    {
        return array_column((array) $this, $key);
    }

    /**
     * Validates a number to be used as an index.
     *
     * @param int $index The number to be validated as an index
     *
     * @throws OutOfRangeException
     * @throws InvalidArgumentException
     */
    private function validateIndex(int $index)
    {
        $exists = $this->indexExists($index);

        if (!$exists) {
            throw new OutOfRangeException('Index out of bounds of collection');
        }
    }

    /**
     * Returns true if $index is within the collection's range and returns false
     * if it is not.
     *
     * @param int $index
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function indexExists(int $index)
    {
        if ($index < 0) {
            throw new InvalidArgumentException('Index must be a non-negative integer');
        }

        return $index < $this->count();
    }

    /**
     * Finding the first element in the Array, for which $callback returns true.
     *
     * @param callable $callback
     *
     * @return T Element Found in the Array or null
     */
    public function find(callable $callback)
    {
        foreach ($this as $element) {
            if ($callback($element)) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Filtering the array by retrieving only these elements for which callback returns true.
     *
     * @param callable $callback
     * @param int      $flag     Use ARRAY_FILTER_USE_KEY to pass key as the only argument to $callback instead of value.
     *                           Use ARRAY_FILTER_USE_BOTH pass both value and key as arguments to $callback instead of value.
     *
     * @return static
     *
     * @see array_filter
     */
    public function filter(callable $callback, int $flag = 0)
    {
        $data = $this->getArrayCopy();
        $result = array_filter($data, $callback, $flag);

        return new static($result);
    }

    /**
     * Reset the array pointer to the first element and return the element.
     *
     * @throws \OutOfBoundsException
     *
     * @return T
     */
    public function first()
    {
        if ($this->count() === 0) {
            throw new \OutOfBoundsException('Cannot get first element of empty Collection');
        }

        return $this->at(0);
    }

    /**
     * Reset the array pointer to the last element and return the element.
     *
     * @throws \OutOfBoundsException
     *
     * @return T
     */
    public function last()
    {
        if ($this->count() === 0) {
            throw new \OutOfBoundsException('Cannot get last element of empty Collection');
        }

        return $this->at($this->count() - 1);
    }

    /**
     * Apply a user supplied function to every member of an array.
     *
     * @see array_reverse
     *
     * @param bool $preserveKeys
     *
     * @return static
     */
    public function reverse(bool $preserveKeys = false)
    {
        return new static(array_reverse($this->getArrayCopy(), $preserveKeys));
    }

    /**
     * Get list of keys.
     *
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->getArrayCopy());
    }

    /**
     * Use a user supplied callback to reduce the array to a single member and return it.
     *
     * @param callable   $callback
     * @param mixed|null $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->getArrayCopy(), $callback, $initial);
    }

    /**
     * Convert dto to array.
     *
     * @param bool $trim     - Remove NULL properties
     * @param bool $sanitize - Perform security cleaning
     *
     * @return array
     */
    public function toArray($trim = true, $sanitize = false): array
    {
        $result = [];
        foreach ($this as $item) {
            if (!$trim || $item !== null) {
                if ($item instanceof static) {
                    $result[] = $item->toArray($trim);
                } elseif ($item instanceof DataObject) {
                    $result[] = $item->toArray($trim);
                } elseif (is_array($item)) {
                    // Item class provided
                    if ($this->itemClass) {
                        // Item class is subclass of BaseDto
                        if (in_array(BaseDto::class, class_parents($this->itemClass) ?: [])) {
                            $result[] = array_is_list($item) ? (new static($item))->toArray($trim) : call_user_func([$this->itemClass, 'with'], $item)->toArray($trim);
                        }
                        // Item class is a custom class
                        else {
                            $result[] = array_is_list($item) ? (new static($item))->toArray($trim) : $item;
                        }
                    }
                    // Item class not provided - load with custom data
                    else {
                        $result[] = array_is_list($item) ? (new static($item))->toArray($trim) : $item;
                    }
                } else {
                    $value = $this->resolveType($this->findType($item), $item);
                    if ($value instanceof Stringable) {
                        $result[] = strval($value);
                    } else {
                        $result[] = $value;
                    }
                }
            }
        }

        return $sanitize ? Security::cleanParams($result) : $result;
    }

    /**
     * Load dto with array.
     *
     * @param iterable<T>      $data
     * @param ?class-string<T> $itemClass
     */
    public static function of(iterable $data, ?string $itemClass = null): static
    {
        return new static($data, $itemClass);
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
