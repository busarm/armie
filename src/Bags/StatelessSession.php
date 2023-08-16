<?php

namespace Armie\Bags;

use Armie\Errors\SessionError;
use Armie\Handlers\EncryptedSessionHandler;
use Armie\Interfaces\SessionStoreInterface;

use SessionHandler;
use SessionHandlerInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @link https://github.com/josantonius/php-session
 */
final class StatelessSession extends Bag implements SessionStoreInterface
{
    protected string|null $id = null;
    protected SessionHandler|SessionHandlerInterface|null $handler = null;

    /**
     * @param string $name Session Name
     * @param string $secret Encryption key
     * @param SessionHandler|SessionHandlerInterface|null $handler Session handler
     * @throws SessionError
     */
    public function __construct(private string $name, private string|null $secret = null, SessionHandler|SessionHandlerInterface|null $handler = null)
    {
        parent::__construct();
        $this->setHandler($handler ?? new EncryptedSessionHandler($secret));
    }

    /**
     * Start session
     *
     * @param string $id
     * @return bool
     */
    public function start($id = null): bool
    {
        $this->id = !empty($id) ? $id : sha1(uniqid(bin2hex(random_bytes(8))));
        $this->checkSessionId($this->id);

        $data = $this->handler->read($this->id);
        if (!empty($data)) {
            $this->load(unserialize($data) ?? []);
            return true;
        } else {
            if ($this->handler->write($this->id, serialize([]))) {
                $this->load([]);
                return true;
            }
        }
        return false;
    }

    /**
     * Save session
     *
     * @return bool
     */
    public function save(): bool
    {
        if (!empty($this->updates())) {
            $this->handler->write($this->id, serialize($this->all()));
            return $this->handler->close();
        }
        return true;
    }

    /**
     * Destroy session
     *
     * @return bool
     */
    public function destroy(): bool
    {
        parent::clear();
        return $this->handler->destroy($this->id);
    }

    /**
     * Regenerate session
     *
     * @param bool $deleteOld
     * @return bool
     */
    public function regenerate(bool $deleteOld = false): bool
    {
        $deleteOld && $this->handler->destroy($this->id);
        return $this->start();
    }

    /**
     * Get session store name.
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get current session ID.
     * 
     * @return string|null
     */
    public function getId(): string|null
    {
        return $this->id;
    }

    /**
     * Set current session ID.
     *
     * @param string $sessionId
     * 
     * @throws SessionError
     * @return self
     */
    public function setId(string $sessionId): self
    {
        $this->throwIfStarted();

        $this->id = $sessionId;

        return $this;
    }

    /**
     * Set session handler
     *
     * @param SessionHandler|SessionHandlerInterface $handler
     * @return self
     */
    public function setHandler(SessionHandler|SessionHandlerInterface|null $handler): self
    {
        if ($handler) {
            $this->throwIfStarted();
            $this->handler = $handler;
        }

        return $this;
    }


    //--------- Utils --------//


    /**
     * Checks if the session is started.
     */
    public function isStarted(): bool
    {
        return $this->getId() != null;
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
     * Check session id.
     *
     * @param string $sessionId
     */
    private static function checkSessionId($sessionId)
    {
        if (!\preg_match('/^[a-zA-Z0-9]+$/', $sessionId)) {
            throw new SessionError("session_id $sessionId is invalid");
        }
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

    public function __destruct()
    {
        $this->save();
    }
}
