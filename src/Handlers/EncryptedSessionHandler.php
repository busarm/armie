<?php

namespace Armie\Handlers;

use Armie\Crypto;
use SessionHandler;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class EncryptedSessionHandler extends SessionHandler
{
    public function __construct(private string|null $key = null)
    {
    }

    /**
     * Read session data
     * Reads the session data from the session storage, and returns the results. Called right after the session starts or when session_start() is called. Please note that before this method is called SessionHandlerInterface::open() is invoked.
     *
     * @param string $id The session id.
     *
     * @return string|false Returns an encoded string of the read data. If nothing was read, it must return `false` . Note this value is returned internally to PHP for processing.
     */
    public function read($id): string|false
    {
        $data = parent::read($id);
        if ($this->key && $data) {
            return Crypto::decrypt($data, $this->key);
        }

        return $data;
    }

    /**
     * Write session data
     * Writes the session data to the session storage. Called by session_write_close(), when session_register_shutdown() fails, or during a normal shutdown. Note: SessionHandlerInterface::close() is called immediately after this function.
     *
     * @param string $id   The session id.
     * @param string $data The encoded session data. This data is the result of the PHP internally encoding the $_SESSION superglobal to a serialized string and passing it as this parameter. Please note sessions use an alternative serialization method.
     *
     * @return bool The return value (usually `true` on success, `false` on failure). Note this value is returned internally to PHP for processing.
     */
    public function write($id, $data): bool
    {
        if ($this->key && $data) {
            $data = Crypto::encrypt($data, $this->key);
        }

        return parent::write($id, $data);
    }
}
