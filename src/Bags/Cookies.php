<?php

namespace Busarm\PhpMini\Bags;

use Busarm\PhpMini\Crypto;

use function Busarm\PhpMini\Helpers\app;
use function Busarm\PhpMini\Helpers\log_debug;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @link https://github.com/josantonius/php-session
 */
class Cookies extends Attribute
{
    /**
     * @param array $options Cookies config options
     * List of available `$options` with their default values:
     *
     * * domain: ""
     * * httponly: "0"
     * * expires: "0" (seconds)
     * * path: "/"
     * * samesite: ""
     * * secure: "0"
     * 
     * @param boolean $encrypt Encrypt Cookies
     * @param string $id Unique id for Encrypted cookie. Use to bind cookies to specific user. E.g Ip address
     * @param string $prefix Prefix for cookies
     */
    public function __construct(private array $options = [], private bool $encrypt = true, private string $id = '', private string $prefix = '')
    {
        parent::__construct([]);

        $this->prefix = !empty($prefix) ? $prefix : (!empty(app()->config->cookiePrefix) ?
            app()->config->cookiePrefix :
            str_replace(' ', '_', strtolower(app()->config->name)));
    }

    /**
     * Load cookies
     *
     * @return self
     */
    public function load($cookies): self
    {
        $this->attributes = $cookies;
        return $this;
    }

    /**
     * Set attribute
     *
     * @param string $name
     * @param mixed $value
     *
     * @return bool
     */
    function set(string $name, mixed $value): bool
    {
        $name = $this->prefix . '_' . $name;
        $value = !empty($value) ?
            ($this->encrypt && !empty(app()->config->encryptionKey) ?
                Crypto::encrypt(app()->config->encryptionKey . ($this->id ? md5($this->id) : ''), $value) :
                $value) :
            "";
            
        parent::set($name, $value);
        return setcookie(
            $name,
            $value,
            $this->options
        );
    }

    /**
     * Get attribute
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    function get(string $name, $default = null): mixed
    {
        $name = $this->prefix . '_' . $name;
        $value = $this->has($name) ? $this->attributes[$name] : null;
        if (!empty($value)) {
            return ($this->encrypt && !empty(app()->config->encryptionKey)) ?
                (Crypto::decrypt(app()->config->encryptionKey . ($this->id ? md5($this->id) : ''), $value) ?: NULL) :
                $value;
        }
        return $default;
    }

    /**
     * Remove attribute
     *
     * @param string $name
     *
     * @return void
     */
    function remove(string $name)
    {
        setcookie($this->prefix . '_' . $name, '', 0);
        parent::remove($name);
    }

    /**
     * Remove all attribute
     *
     * @return void
     */
    function clear()
    {
        foreach (array_keys($this->attributes) as $name) $this->remove($name);
    }
}
