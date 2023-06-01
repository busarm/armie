<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Crypto;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @link https://github.com/josantonius/php-session
 */
class Cookie extends Attribute
{
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
        parent::__construct([]);
    }

    /**
     * Load cookies
     * 
     * @param array $cookies
     * @return self
     */
    public function load(array $cookies): self
    {
        foreach ($cookies as $name => $cookie) {
            if (!$this->has($name))
                $this->set($name, $cookie);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, mixed $value, $options = NULL): bool
    {
        $name = str_starts_with($name, $this->prefix) ? $name : $this->prefix . '_' . $name;
        $value = !empty($value) ?
            (!empty($this->secret) ?
                Crypto::encrypt($this->secret, $value) :
                $value) :
            "";
        $options = is_int($options) ?
            array_merge($this->options, ['expires' => time() + $options]) : (is_array($options) ? array_merge($this->options, $options) : $this->options);

        parent::set($name, $value);
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
        $name = $this->prefix . '_' . $name;
        $value = $this->has($name) ? $this->attributes[$name] : null;
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
    public function remove(string $name)
    {
        $name = $this->prefix . '_' . $name;
        setcookie($name, '', 0);
        parent::remove($name);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        foreach (array_keys($this->attributes) as $name) $this->remove($name);
    }
}
