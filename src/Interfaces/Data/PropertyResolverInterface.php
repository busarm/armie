<?php

namespace Armie\Interfaces\Data;

use Armie\Interfaces\Arrayable;
use JsonSerializable;
use ReflectionProperty;
use Stringable;

/**
 *  
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
interface PropertyResolverInterface extends Arrayable, Stringable, JsonSerializable
{
    /**
     * Get properties
     *
     * @param bool $all
     * @return ReflectionProperty[]
     */
    public function properties($all = false): array;

    /**
     * Get field names & types
     *
     * @param bool $all Get all or only public field
     * @param bool $trim Get only initialized field
     * @return array<string,string> `[name => type]`. eg. `['id' => 'int']`
     */
    public function fields($all = true, $trim = false): array;

    /**
     * Quickly load data from array to class properties - Without processing property types and attributes
     *
     * @param array $data
     * @param bool $sanitize
     * @return static
     */
    public function fastLoad(array $data, $sanitize = false): static;

    /**
     * Load data from array to class properties
     *
     * @param array $data
     * @param bool $sanitize
     * @return static
     */
    public function load(array $data, $sanitize = false): static;

    /**
     * Is Dirty - Update has been made
     *
     * @return bool
     */
    public function isDirty(): bool;

    /**
     * Get explicitly selected fields
     *
     * @return array
     */
    public function selected(): array;

    /**
     * Explicitly select fields
     *
     * @param array $fields
     * @return static
     */
    public function select(array $fields): static;

    /**
     * Get property
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set property
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set(string $key, mixed $value = null): mixed;

    /**
     * Convert to array
     * 
     * @param bool $trim - Remove NULL properties
     * @param bool $sanitize - Perform security cleaning
     * @return array
     */
    public function toArray($trim = true, $sanitize = false): array;
}
