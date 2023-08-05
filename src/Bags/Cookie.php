<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Crypto;
use Busarm\PhpMini\Interfaces\StorageBagInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @link https://github.com/josantonius/php-session
 * @inheritDoc
 */
final class Cookie implements StorageBagInterface
{
    protected array $original = [];

    /**
     * @param array $options Cookie config options
     * List of available `$options` with their default values:
     *
     * * domain: ""
     * * httponly: "0"
     * * expires: "0" (seconds)
     * * path: "/"
     * * samesite: ""
     * * secure: "0"
     * 
     * @param string|null $prefix Prefix for cookies
     * @param string|null $secret Cookie Secret for encryption
     */
    public function __construct(private array $options = [], private string|null $prefix = '', private string|null $secret = null)
    {
        $this->load($_COOKIE);
    }

    /**
     * Get key exact key for name
     *
     * @param string $name
     * @return string
     */
    public function key(string $name): string
    {
        return str_starts_with($name, $this->prefix) ? $name : $this->prefix . '_' . $name;
    }

    /**
     * @inheritDoc
     */
    public function load(array $cookies): self
    {
        if ($cookies !=  $_COOKIE) {
            foreach ($cookies as $name => $cookie) {
                $_COOKIE[$this->key($name)] = $cookie;
            }
        }

        $this->original = $this->all();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, mixed $cookie, $options = NULL): bool
    {
        $name = $this->key($name);
        $value = !empty($cookie) ?
            (!empty($this->secret) ?
                Crypto::encrypt($this->secret, $cookie) :
                $cookie) :
            "";
        $options = is_int($options) ?
            // Is int - add expiry
            array_merge($this->options, ['expires' => time() + $options]) :
            // Is array - merge options
            (is_array($options) ? array_merge($this->options, $options) : $this->options);

        $_COOKIE[$name] = $value;
        return setcookie(
            $name,
            $value,
            $options
        );
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, $default = null, $sanitize = false): mixed
    {
        $name = $this->key($name);
        $value =  $this->has($name) ?  $_COOKIE[$name] : null;
        if (!empty($value)) {
            return !empty($this->secret) ?
                (Crypto::decrypt($this->secret, $value) ?: NULL) :
                $value;
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
    function has(string $name): bool
    {
        return isset($_COOKIE[$this->key($name)]);
    }

    /**
     * @inheritDoc
     */
    function all(): array
    {
        return $_COOKIE;
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length): array
    {
        return array_slice($_COOKIE, $offset, $length);
    }

    /**
     * @inheritDoc
     */
    public function updates(): array
    {
        return array_filter($_COOKIE, fn ($k) => !isset($this->original[$k]) || $this->original[$k] != $_COOKIE[$k], ARRAY_FILTER_USE_KEY);
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
        setcookie($name, '', -1);
        unset($_COOKIE[$name]);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        foreach (array_keys($_COOKIE) as $name) $this->remove($name);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Gets a string representation of the object
     *
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return json_encode($this->all());
    }
}
