<?php

namespace Armie\Interfaces;

use SessionHandler;
use SessionHandlerInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface SessionStoreInterface extends StorageBagInterface
{
    /**
     * Check session status
     *
     * @return bool
     */
    public function isStarted(): bool;

    /**
     * Start session
     *
     * @param string $id
     * @return bool
     */
    public function start($id = null): bool;
    /**
     * Save session
     *
     * @return bool
     */
    public function save(): bool;
    /**
     * Destroy session
     *
     * @return bool
     */
    public function destroy(): bool;
    /**
     * Regenerate session ID
     *
     * @param bool $deleteOld
     * @return bool
     */
    public function regenerate(bool $deleteOld = false): bool;

    /**
     * Get session store name.
     * 
     * @return string
     */
    public function getName(): string;
    /**
     * Get current session ID.
     * 
     * @return string
     */
    public function getId(): string|null;
    /**
     * Set current session ID.
     *
     * @param string $sessionId
     * @return self
     */
    public function setId(string $sessionId): self;
    /**
     * Set session handler
     *
     * @param SessionHandler|SessionHandlerInterface $handler
     * @return self
     */
    public function setHandler(SessionHandler|SessionHandlerInterface $handler): self;
}
