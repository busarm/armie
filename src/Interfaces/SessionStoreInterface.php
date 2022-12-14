<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Interfaces\StorageBagInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface SessionStoreInterface extends StorageBagInterface
{
    /**
     * Start session
     *
     * @return bool
     */
    public function start(): bool;
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
    public function getId(): string;
    /**
     * Set current session ID.
     *
     * @param string $sessionId
     * @return void
     */
    public function setId(string $sessionId): void;
    /**
     * Touch session to update last access date
     *
     * @param string $name
     * @return void
     */
    public function touch(string $name);
    /**
     * Regenerate session ID
     *
     * @param bool $deleteOld
     * @return bool
     */
    public function regenerate(bool $deleteOld = false): bool;
    /**
     * Destroy session
     *
     * @return bool
     */
    public function destroy(): bool;
}
