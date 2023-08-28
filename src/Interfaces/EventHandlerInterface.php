<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface EventHandlerInterface
{
    /**
     * Dispatch event.
     *
     * @param string $event
     * @param array  $data
     *
     * @return void
     */
    public function dispatch(string $event, array $data = []): void;

    /**
     * Add event listner.
     *
     * @param string                          $event
     * @param callable|class-string<Runnable> $listner
     *
     * @return void
     */
    public function listen(string $event, callable|string $listner): void;
}
