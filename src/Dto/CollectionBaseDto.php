<?php

namespace Busarm\PhpMini\Dto;

use ArrayIterator;
use ArrayObject;
use InvalidArgumentException;
use OutOfRangeException;
use Traversable;

use Busarm\PhpMini\Interfaces\Arrayable;
/**
 * PHP Mini Framework
 *
 * @see https://stackoverflow.com/a/54096881
 * 
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class CollectionBaseDto extends ArrayObject implements Arrayable
{
    /**
     * Define the class that will be used for all items in the array.
     * To be defined in each sub-class.
     */
    const ITEM_CLASS = NULL;

    /**
     * Constructor
     *
     * Store the required array type prior to parental construction.
     *
     * @param array|object $input Any data to preset the array to.
     * @param int $flags The flags to control the behaviour of the ArrayObject.
     * @param string $iteratorClass Specify the class that will be used for iteration of the ArrayObject object. ArrayIterator is the default class used.
     *
     * @throws InvalidArgumentException
     */
    protected function __construct($input = [], $flags = 0, $iteratorClass = ArrayIterator::class)
    {
        // Create an empty array.
        parent::__construct([], $flags, $iteratorClass);

        // Load data
        $this->load($input);
    }

    /**
     * Load data
     *
     * @param array|Traversable $data
     * @return self
     */
    public function load(array|Traversable $data): self
    {
        // Validate that the input is an array or an object with an Traversable interface.
        if (!(is_array($data) || (is_object($data) && in_array(Traversable::class, class_implements($data))))) {
            throw new InvalidArgumentException('$input must be an array or an object that implements \Traversable.');
        }

        // Append each item so to validate it's type.
        foreach ($data as $key => $value) {
            // Validate Item type if available
            if (!empty(static::ITEM_CLASS) && !($value instanceof (self::ITEM_CLASS))) {
                throw new InvalidArgumentException('Items of $input must be an instance of ' . self::ITEM_CLASS);
            }
            $this[$key] = $value;
        }

        return $this;
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
     * Inserting the provided element at the index. If index is negative, it will be calculated from the end of the Array Object
     *
     * @param int $index
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
     * @param int $offset
     * @param int|null $length
     * @param null $replacement
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

        return new self($result);
    }

    /**
     * Adding a new value at the beginning of the collection
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
     * @param int $offset
     * @param int|null $length
     * @param bool $preserveKeys
     *
     * @return static
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = false)
    {
        return new self(array_slice($this->getArrayCopy(), $offset, $length, $preserveKeys));
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
     * Apply a user supplied function to every member of an array
     *
     * @see array_walk
     *
     * @param callable $callback
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
     * Chunks the object into ArrayObject containing
     *
     * @param int $size
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
     * @return ArrayObject A collection of the results of $mapper(mixed $item)
     */
    public function map(callable $mapper): ArrayObject
    {
        $data = $this->getArrayCopy();
        $result = array_map($mapper, $data);

        return new self($result);
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
     * @return mixed
     *
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     */
    public function at(int $index)
    {
        $this->validateIndex($index);

        return $this[$index];
    }

    /**
     * Validates a number to be used as an index
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
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    public function indexExists(int $index)
    {
        if ($index < 0) {
            throw new InvalidArgumentException('Index must be a non-negative integer');
        }

        return $index < $this->count();
    }

    /**
     * Finding the first element in the Array, for which $callback returns true
     *
     * @param callable $callback
     *
     * @return mixed Element Found in the Array or null
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
     * Filtering the array by retrieving only these elements for which callback returns true
     *
     * @param callable $callback
     * @param int $flag Use ARRAY_FILTER_USE_KEY to pass key as the only argument to $callback instead of value.
     *                  Use ARRAY_FILTER_USE_BOTH pass both value and key as arguments to $callback instead of value.
     *
     * @return static
     *
     * @see array_filter
     */
    public function filter(callable $callback, int $flag = 0)
    {
        $data = $this->getArrayCopy();
        $result = array_filter($data, $callback, $flag);

        return new self($result);
    }

    /**
     * Reset the array pointer to the first element and return the element.
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException
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
     * @return mixed
     *
     * @throws \OutOfBoundsException
     */
    public function last()
    {
        if ($this->count() === 0) {
            throw new \OutOfBoundsException('Cannot get last element of empty Collection');
        }

        return $this->at($this->count() - 1);
    }

    /**
     * Apply a user supplied function to every member of an array
     *
     * @see array_reverse
     *
     * @param bool $preserveKeys
     *
     * @return static
     */
    public function reverse(bool $preserveKeys = false)
    {
        return new self(array_reverse($this->getArrayCopy(), $preserveKeys));
    }

    public function keys(): array
    {
        return array_keys($this->getArrayCopy());
    }

    /**
     * Use a user supplied callback to reduce the array to a single member and return it.
     *
     * @param callable $callback
     * @param mixed|null $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->getArrayCopy(), $callback, $initial);
    }

    /**
     * Get array response data
     * @param bool $trim Remove NULL properties
     * @return array
     */
    public function toArray($trim = true): array
    {
        $result = [];
        foreach ($this as $key => $item) {
            if ((!$trim || $item !== NULL)) {
                if ($item instanceof self) {
                    $result[$key] = $item->toArray();
                } else if ($item instanceof BaseDto) {
                    $result[$key] = $item->toArray();
                } else if (is_array($item)) {
                    foreach ($item as &$data) {
                        if ($data instanceof self) {
                            $data = $data->toArray();
                        } else if ($data instanceof BaseDto) {
                            $data = $data->toArray();
                        } else {
                            $data = BaseDto::parseType(BaseDto::resolveType($data), $data);
                        }
                    }
                    $result[$key] = $item;
                } else {
                    $result[$key] = BaseDto::parseType(BaseDto::resolveType($item), $item);
                }
            }
        }
        return $result;
    }

    /**
     * Load dto with array
     *
     * @param array|object $data
     * @return static
     */
    public static function of(array|object $data): static
    {
        return new self($data);
    }
}
