<?php

namespace Busarm\PhpMini\Interfaces\Event;

use Busarm\PhpMini\Interfaces\Runnable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface EventManagerInterface
{
    /**
     * Set list of event listners
     * 
     * @param array<string, callable[]|class-string<Runnable>[]> $listners
     */
    public function setListners(array $listners): void;

    /**
     * Get list of event listners
     * 
     * @return array<string, callable[]|class-string<Runnable>[]>
     */
    public function getListners(): array;

    /**
     * Add event listner
     * 
     * @param string $event
     * @param callable|class-string<Runnable> $listner
     */
    public function addEventListner(string $event, callable|string $listner);

    /**
     * Get event listners for event
     *
     * @param string $event
     * @return array<callable|class-string<Runnable>>
     */
    public function getEventListners(string $event): array;

    /**
     * Dispatch event
     * 
     * @param string $event
     * @param array $data
     */
    public function dispatchEvent(string $event, array $data = []);
}
