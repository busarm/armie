<?php

namespace Armie\Bags;

use Armie\Crypto;
use Armie\Dto\CookieDto;
use Armie\Interfaces\StorageBagInterface;
use Generator;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @link https://github.com/josantonius/php-session
 */
final class StatelessCookie implements StorageBagInterface
{
    /**
     * @var array<string, CookieDto>
     */
    protected array $original = [];
    /**
     * @var array<string, CookieDto>
     */
    protected array $data = [];

    /**
     * @param array $options Cookie config options
     *                       List of available `$options` with their default values:
     *
     * * domain: ""
     * * httponly: "0"
     * * expires: "0" (seconds)
     * * path: "/"
     * * samesite: ""
     * * secure: "0"
     * @param string|null $prefix Prefix for cookies
     * @param string|null $secret Cookie Secret for encryption
     */
    public function __construct(private array $options = [], private string|null $prefix = '', private string|null $secret = null)
    {
    }

    /**
     * Get key exact key for name.
     *
     * @param string $name
     *
     * @return string
     */
    public function key(string $name): string
    {
        return str_starts_with($name, $this->prefix) ? $name : $this->prefix.'_'.$name;
    }

    /**
     * @inheritDoc
     */
    public function load(array $cookies): self
    {
        foreach ($cookies as $name => $value) {
            $this->data[$this->key($name)] = new CookieDto($name, $value, $this->options);
        }

        $this->original = $this->all();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, mixed $cookie, $options = null): bool
    {
        if ($this->get($name) == $cookie) {
            return true;
        }

        $name = $this->key($name);
        $value = !empty($cookie) ?
            (!empty($this->secret) ?
                Crypto::encrypt($this->secret, $cookie) :
                $cookie) :
            '';
        $options = is_int($options) ?
            // Is int - add expiry
            array_merge($this->options, ['expires' => time() + $options]) :
            // Is array - merge options
            (is_array($options) ? array_merge($this->options, $options) : $this->options);

        $this->data[$name] = new CookieDto($name, $value, $options);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, $default = null, $sanitize = false): mixed
    {
        $name = $this->key($name);
        $data = $this->has($name) ? $this->data[$name] : null;
        if (!empty($data)) {
            return !empty($this->secret) ?
                (Crypto::decrypt($this->secret, $data->value) ?: null) :
                $data->value;
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function pull(string $name, $default = null, $sanitize = false): mixed
    {
        $value = $this->get($name, $default, $sanitize);
        $this->remove($name);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return isset($this->data[$this->key($name)]);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->data ?? [];
    }

    /**
     * @inheritDoc
     */
    public function updates(): array
    {
        return array_filter($this->data, fn ($v, $k) => !isset($this->original[$k]) || strval($this->original[$k]) != strval($v), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @inheritDoc
     */
    public function itterate(bool $delete = false): Generator
    {
        foreach ($this->data as $key => $item) {
            if ($delete) {
                $this->remove($key);
            }
            yield $key => $item;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function replace(array $data)
    {
        foreach ($data as $name => $cookie) {
            $this->set($name, $cookie);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(string $name)
    {
        $name = $this->key($name);
        unset($this->data[$name]);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        foreach (array_keys($this->data) as $name) {
            $this->remove($name);
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Gets a string representation of the object.
     *
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return json_encode($this->all());
    }
}
