<?php

namespace Busarm\PhpMini\Interfaces\Bags;

use Busarm\PhpMini\Interfaces\Bags\AttributeBag;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface SessionBag extends AttributeBag
{
    /**
     * Start session
     *
     * @return bool
     */
    public function start(): bool;
    /**
     * Gets the session name.
     * 
     * @return string
     */
    public function getName(): string;
    /**
     * Gets the session ID.
     */
    public function getId(): string;
    /**
     * Sets the session ID.
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
