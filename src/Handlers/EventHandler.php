<?php

namespace Armie\Handlers;

use Armie\App;
use Armie\Async;
use Armie\Enums\AppStatus;
use Armie\Errors\SystemError;
use Armie\Interfaces\EventHandlerInterface;
use Armie\Interfaces\Runnable;


/**
 * Handle event operations
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
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

        // App running in async mode - register as single-use
        if ($this->app->status === AppStatus::RUNNNIG && $this->app->async) {
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
            // Use event loop
            if ($this->app->async && $this->app->worker) {
                Async::withEventLoop(fn () => call_user_func($listner, $data));
            }
            // Use default
            else {
                Async::withChildProcess(fn () => call_user_func($listner, $data)) or call_user_func($listner, $data);
            }
        } else {
            $task = $this->app->di->instantiate($listner, null, $data);
            if ($task instanceof Runnable) {
                // Use event loop
                if ($this->app->async && $this->app->worker) {
                    Async::withEventLoop($task);
                }
                // Use default
                else {
                    Async::withChildProcess($task) or $task->run();
                }
            }
        }
    }
}
