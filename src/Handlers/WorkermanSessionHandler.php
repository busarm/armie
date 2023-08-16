<?php

namespace Armie\Handlers;

use Armie\Crypto;
use SessionHandlerInterface;
use Workerman\Protocols\Http\Session\SessionHandlerInterface as WorkermanSessionHandlerInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class WorkermanSessionHandler implements SessionHandlerInterface
{
    public function __construct(private WorkermanSessionHandlerInterface $handler, private string|null $key = null)
    {
    }

    /**
     * Close the session
     * Closes the current session. This function is automatically executed when closing the session, or explicitly via session_write_close().
     * @return bool The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
     */
    public function close(): bool
    {
        return $this->handler->close();
    }

    /**
     * Destroy a session
     * Destroys a session. Called by session_regenerate_id() (with $destroy = `true` ), session_destroy() and when session_decode() fails.
     *
     * @param string $id The session ID being destroyed.
     * @return bool The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
     */
    public function destroy($id): bool
    {
        return $this->handler->destroy($id);
    }

    /**
     * Cleanup old sessions
     * Cleans up expired sessions. Called by session_start(), based on session.gc_divisor , session.gc_probability and session.gc_maxlifetime settings.
     *
     * @param int $max_lifetime Sessions that have not updated for the last `max_lifetime` seconds will be removed.
     * @return int|false Returns the number of deleted sessions on success, or `false` on failure. Note this value is returned internally to PHP for processing.
     */
    public function gc($max_lifetime): int|false
    {
        $done = $this->handler->gc($max_lifetime);
        return is_int($done) ? $done : false;
    }

    /**
     * Initialize session
     * Re-initialize existing session, or creates a new one. Called when a session starts or when session_start() is invoked.
     *
     * @param string $path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
     */
    public function open($path, $name): bool
    {
        return $this->handler->open($path, $name);
    }

    /**
     * Read session data
     * Reads the session data from the session storage, and returns the results. Called right after the session starts or when session_start() is called. Please note that before this method is called SessionHandlerInterface::open() is invoked.
     *
     * @param string $id The session id.
     * @return string|false Returns an encoded string of the read data. If nothing was read, it must return `false` . Note this value is returned internally to PHP for processing.
     */
    public function read($id): string|false
    {
        $data = $this->handler->read($id);
        if ($this->key && $data) {
            return Crypto::decrypt($this->key, $data) ?: false;
        }
        return $data;
    }

    /**
     * Write session data
     * Writes the session data to the session storage. Called by session_write_close(), when session_register_shutdown() fails, or during a normal shutdown. Note: SessionHandlerInterface::close() is called immediately after this function.
     *
     * @param string $id The session id.
     * @param string $data The encoded session data. This data is the result of the PHP internally encoding the $_SESSION superglobal to a serialized string and passing it as this parameter. Please note sessions use an alternative serialization method.
     * @return bool The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
     */
    public function write($id, $data): bool
    {
        if ($this->key && $data) {
            $data = Crypto::encrypt($this->key, $data);
        }
        return $this->handler->write($id, $data);
    }
}
