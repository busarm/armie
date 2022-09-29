<?php

namespace Busarm\PhpMini\Session;

use Busarm\PhpMini\Errors\SessionError;
use Busarm\PhpMini\Interfaces\Bags\SessionBag;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @link https://github.com/josantonius/php-session
 */
class PHPSession implements SessionBag
{
    /**
     * @param array $options
     * List of available `$options` with their default values:
     *
     * * cache_expire: "180" (minutes)
     * * cache_limiter: "nocache"
     * * cookie_domain: ""
     * * cookie_httponly: "0"
     * * cookie_lifetime: "0"
     * * cookie_path: "/"
     * * cookie_samesite: ""
     * * cookie_secure: "0"
     * * gc_divisor: "100"
     * * gc_maxlifetime: "1440"
     * * gc_probability: "1"
     * * lazy_write: "1"
     * * name: "PHPSESSID"
     * * read_and_close: "0"
     * * referer_check: ""
     * * save_handler: "files"
     * * save_path: ""
     * * serialize_handler: "php"
     * * sid_bits_per_character: "4"
     * * sid_length: "32"
     * * trans_sid_hosts: $_SERVER['HTTP_HOST']
     * * trans_sid_tags: "a=href,area=href,frame=src,form="
     * * use_cookies: "1"
     * * use_only_cookies: "1"
     * * use_strict_mode: "0"
     * * use_trans_sid: "0"
     * 
     * @throws SessionError
     */
    public function __construct(private array $options = [])
    {
        $this->throwIfHasWrongOptions($this->options);
    }

    /**
     * Start session
     *
     * @throws SessionError
     * @return bool
     */
    function start(): bool
    {
        $this->throwIfHeadersSent();
        $this->throwIfStarted();
        return session_start($this->options);
    }

    /**
     * Gets the session name.
     * 
     * @return string
     */
    public function getName(): string
    {
        $name = session_name();

        return $name ? $name : '';
    }

    /**
     * Gets the session ID.
     * 
     * @return string
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Sets the session ID.
     *
     * @param string $sessionId
     * 
     * @throws SessionError
     * @return void
     */
    public function setId(string $sessionId): void
    {
        $this->throwIfNotStarted();

        session_id($sessionId);
    }

    /**
     * Touch session to update last access or expiry date
     *
     * @param string $name
     *
     * @return void
     */
    function touch(string $name)
    {
    }

    /**
     * Deletes an attribute by name and returns its value.
     *
     * @param string $name
     * @param mixed $default
     *
     * @throws SessionError
     * @return mixed
     */
    function pull(string $name, mixed $default = null): mixed
    {
        $this->throwIfNotStarted();

        $value = $_SESSION[$name] ?? $default;

        unset($_SESSION[$name]);

        return $value;
    }

    /**
     * Regenerate session
     *
     * @param bool $deleteOld
     * @return bool
     */
    function regenerate(bool $deleteOld = false): bool
    {
        return session_regenerate_id($deleteOld);
    }

    /**
     * Set attribute
     *
     * @param string $name
     * @param mixed $value
     * @param mixed $options
     *
     * @throws SessionError
     * @return bool
     */
    function set(string $name, mixed $value, $options = NULL): bool
    {
        $this->throwIfNotStarted();

        $_SESSION[$name] = $value;
        return true;
    }

    /**
     * Checks if an attribute exists
     *
     * @param string $name
     *
     * @return bool
     */
    function has(string $name): bool
    {
        return isset($_SESSION[$name]);
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
        return $_SESSION[$name] ?? $default;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    function all(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * Set bulk attributes
     *
     * @param array $data
     *
     * @throws SessionError
     * @return void
     */
    function replace(array $data)
    {
        $this->throwIfNotStarted();

        $_SESSION = array_merge($_SESSION, $data);
    }

    /**
     * Remove attribute
     *
     * @param string $name
     * 
     * @throws SessionError
     * @return void
     */
    function remove(string $name)
    {
        if ($this->has($name)) unset($_SESSION[$name]);
    }

    /**
     * Remove all attribute
     *
     * @throws SessionError
     * @return void
     */
    function clear()
    {
        $this->throwIfNotStarted();

        session_unset();
    }

    /**
     * Destroy session
     *
     * @throws SessionError
     * @return bool
     */
    public function destroy(): bool
    {

        $this->throwIfNotStarted();

        return session_destroy();
    }

    /**
     * Checks if the session is started.
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Throw exception if the session have wrong options.
     *
     * @throws SessionError
     */
    private function throwIfHasWrongOptions(array $options): void
    {
        $validOptions = array_flip([
            'cache_expire',    'cache_limiter',     'cookie_domain',          'cookie_httponly',
            'cookie_lifetime', 'cookie_path',       'cookie_samesite',        'cookie_secure',
            'gc_divisor',      'gc_maxlifetime',    'gc_probability',         'lazy_write',
            'name',            'read_and_close',    'referer_check',          'save_handler',
            'save_path',       'serialize_handler', 'sid_bits_per_character', 'sid_length',
            'trans_sid_hosts', 'trans_sid_tags',    'use_cookies',            'use_only_cookies',
            'use_strict_mode', 'use_trans_sid',
        ]);

        foreach (array_keys($options) as $key) {
            if (!isset($validOptions[$key])) {
                throw new SessionError("Invalid session option: $key");
            }
        }
    }

    /**
     * Throw exception if headers have already been sent.
     *
     * @throws SessionError
     */
    private function throwIfHeadersSent(): void
    {
        $headersWereSent = (bool) ini_get('session.use_cookies') && headers_sent($file, $line);

        $headersWereSent && throw new SessionError("Header already sent in $file:$line");
    }

    /**
     * Throw exception if the session has already been started.
     *
     * @throws SessionError
     */
    private function throwIfStarted(): void
    {
        $this->isStarted() && throw new SessionError('Session already started');
    }

    /**
     * Throw exception if the session was not started.
     *
     * @throws SessionError
     */
    private function throwIfNotStarted(): void
    {
        !$this->isStarted() && throw new SessionError('Session has not been started');
    }
}
