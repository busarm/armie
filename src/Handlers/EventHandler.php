<?php

namespace Busarm\PhpMini\Handlers;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Async;
use Busarm\PhpMini\Enums\AppStatus;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\EventHandlerInterface;
use Busarm\PhpMini\Interfaces\Runnable;

/**
 * Handle event operations
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class EventHandler implements EventHandlerInterface
{
    /** 
     * Single use listeners - Listners will be removed after use
     * @var array<string, callable[]|class-string<Runnable>[]> 
     */
    private static $singleUseListeners = [];

    /** 
     * Event listners
     * @var array<string, callable[]|class-string<Runnable>[]> 
     */
    private $listners = [];

    /**
     *
     * @param App $app
     * @param integer $maxEventListners Maximum listners allowed per event. **IMPORTANT**: To prevent memory leak
     */
    public function __construct(private App $app, private $maxEventListners = 10)
    {
    }

    /**
     * @inheritDoc
     */
    public function listen(string $event, callable|string $listner): void
    {
        if (is_string($listner) && !in_array(Runnable::class, class_implements($listner))) {
            throw new SystemError("`$listner` does not implement " . Runnable::class);
        }

        // App running - register as single-use
        if ($this->app->status === AppStatus::RUNNNIG) {
            // Empty - initialize
            if (!isset(self::$singleUseListeners[$event])) {
                self::$singleUseListeners[$event] = [];
            }
            // Limit reached - remove earliest
            else if (count(self::$singleUseListeners[$event]) >= $this->maxEventListners) {
                array_shift(self::$singleUseListeners[$event]);
            }
            self::$singleUseListeners[$event][] = $listner;
        }
        // Use default
        else {
            // Empty - initialize
            if (!isset($this->listners[$event])) {
                $this->listners[$event] = [];
            }
            // Limit reached - remove earliest
            else if (count($this->listners[$event]) >= $this->maxEventListners) {
                array_shift($this->listners[$event]);
            }
            $this->listners[$event][] = $listner;
        }
    }

    /**
     * @inheritDoc
     */
    public function dispatch(string $event, array $data = []): void
    {
        $this->app->throwIfNotAsync("Event dispatch is only available when app is running in async mode");

        if (!empty($listners = $this->listners[$event] ?? [])) {
            foreach ($listners as $listner) {
                $this->handle($listner, $data);
            }
        }

        if (!empty($listners = self::$singleUseListeners[$event] ?? [])) {
            foreach ($listners as $listner) {
                $this->handle($listner, $data);
            }
            // Clear events after use
            unset(self::$singleUseListeners[$event]);
        }
    }

    /**
     * Proccess event
     *
     * @param callable|class-string<Runnable> $listner
     * @param array $data
     * @return void
     */
    private function handle(callable|string $listner, array $data = []): void
    {
        if (is_callable($listner)) {
            Async::taskLoop(fn () => call_user_func($listner, $data));
        } else {
            $task = $this->app->di->instantiate($listner, null, $data);
            if ($task instanceof Runnable) {
                Async::taskLoop($task);
            }
        }
    }
}
