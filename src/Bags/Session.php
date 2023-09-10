<?php

namespace Armie\Bags;

use Armie\Errors\SessionError;
use Armie\Handlers\EncryptedSessionHandler;
use Armie\Helpers\Security;
use Armie\Interfaces\SessionStoreInterface;
use Generator;
use SessionHandler;
use SessionHandlerInterface;

use function Armie\Helpers\is_cli;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @link https://github.com/josantonius/php-session
 */
final class Session implements SessionStoreInterface
{
    protected array $original = [];

    /**
     * @param array $options
     *                       List of available `$options` with their default values:
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
     * @param string                                      $secret  Encryption key
     * @param SessionHandler|SessionHandlerInterface|null $handler Session handler
     *
     * @throws SessionError
     */
    public function __construct(private array $options = [], string|null $secret = null, SessionHandler|SessionHandlerInterface|null $handler = null)
    {
        $this->throwIfHasWrongOptions($this->options);
        $this->setHandler($handler ?? new EncryptedSessionHandler($secret));
    }

    /**
     * Start session.
     *
     * @param string $id
     *
     * @return bool
     */
    public function start($id = null): bool
    {
        $this->throwIfHeadersSent();
        $this->throwIfStarted();

        if ($id) {
            $this->setId($id);
        }

        $done = session_start($this->options);
        if ($done) {
            $this->original = $this->all();
        }

        return $done;
    }

    /**
     * Save session.
     *
     * @return bool
     */
    public function save(): bool
    {
        $this->throwIfNotStarted();

        return session_write_close();
    }

    /**
     * Destroy session.
     *
     * @throws SessionError
     *
     * @return bool
     */
    public function destroy(): bool
    {
        $this->throwIfNotStarted();

        return session_destroy();
    }

    /**
     * Regenerate session.
     *
     * @param bool $deleteOld
     *
     * @return bool
     */
    public function regenerate(bool $deleteOld = false): bool
    {
        return session_regenerate_id($deleteOld);
    }

    /**
     * Get session store name.
     *
     * @return string
     */
    public function getName(): string
    {
        $name = session_name();

        return $name ? $name : 'PHPSESS';
    }

    /**
     * Get current session ID.
     *
     * @return string
     */
    public function getId(): string|null
    {
        return session_id();
    }

    /**
     * Set current session ID.
     *
     * @param string $sessionId
     *
     * @throws SessionError
     *
     * @return self
     */
    public function setId(string $sessionId): self
    {
        $this->throwIfStarted();

        session_id($sessionId);

        return $this;
    }

    /**
     * Set session handler.
     *
     * @param SessionHandler|SessionHandlerInterface $handler
     *
     * @return self
     */
    public function setHandler(SessionHandler|SessionHandlerInterface|null $handler): self
    {
        if ($handler) {
            $this->throwIfHeadersSent();
            $this->throwIfStarted();
            session_set_save_handler($handler);
        }

        return $this;
    }

    //--------- Manipulate Session --------//

    /**
     * @inheritDoc
     */
    public function load(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $_SESSION[$key] = $value;
        }
        $this->original = $attributes;

        return $this;
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
        return isset($_SESSION[$name]);
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, $default = null, $sanitize = false): mixed
    {
        return $this->has($name) ? ($sanitize ? Security::clean($_SESSION[$name]) : $_SESSION[$name]) : $default;
    }

    /**
     * @inheritDoc
     *
     * @throws SessionError
     */
    public function set(string $name, mixed $value, $options = null): bool
    {
        $this->throwIfNotStarted();

        $_SESSION[$name] = $value;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * @inheritDoc
     */
    public function updates(): array
    {
        return array_filter($_SESSION, fn ($k) => !isset($this->original[$k]) || $this->original[$k] != $_SESSION[$k], ARRAY_FILTER_USE_KEY);
    }

    /**
     * @inheritDoc
     */
    public function itterate(bool $delete = false): Generator
    {
        foreach ($_SESSION as $key => $item) {
            if ($delete) {
                $this->remove($key);
            }
            yield $key => $item;
        }

        return null;
    }

    /**
     * @inheritDoc
     *
     * @throws SessionError
     */
    public function replace(array $data)
    {
        $this->throwIfNotStarted();

        $_SESSION = array_merge($_SESSION, $data);
    }

    /**
     * @inheritDoc
     *
     * @throws SessionError
     */
    public function remove(string $name)
    {
        $this->throwIfNotStarted();

        if ($this->has($name)) {
            unset($_SESSION[$name]);
        }
    }

    /**
     * @inheritDoc
     *
     * @throws SessionError
     */
    public function clear()
    {
        $this->throwIfNotStarted();

        session_unset();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->all());
    }

    //--------- Utils --------//

    /**
     * Checks if the session is started.
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE && $this->getId() != null;
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
        [$file, $line] = [null, null];
        $headersWereSent = !is_cli() && (bool) ini_get('session.use_cookies') && headers_sent($file, $line);

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
        !$this->isStarted() && throw new SessionError('Session has not been started. Tip: Add SessionMiddleware');
    }

    /**
     * Gets a string representation of the object.
     *
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return json_encode($_SESSION);
    }
}
