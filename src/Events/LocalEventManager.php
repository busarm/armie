<?php

namespace Busarm\PhpMini\Events;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\Event\EventManagerInterface;
use Busarm\PhpMini\Interfaces\Runnable;
use Busarm\PhpMini\Tasks\EventTask;

use function Busarm\PhpMini\Helpers\async;

/**
 * Handle event operations
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class LocalEventManager implements EventManagerInterface
{
    /**
     * Maximum listners allowed per event.
     * **IMPORTANT**: To prevent memory leak
     */
    const MAX_EVENT_LISTENERS = 10;

    /** @var array<string, callable[]|class-string<Runnable>[]> */
    private $listners = [];

    public function __construct(private App $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function setListners(array $listners): void
    {
        $this->listners = [];
        foreach ($listners as $event => $eventListners) {
            if (is_array($eventListners)) {
                foreach ($eventListners as $listner) {
                    $this->addEventListner($event, $listner, true);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getListners(): array
    {
        return $this->listners;
    }

    /**
     * @inheritDoc
     */
    public function addEventListner(string $event, callable|string $listner, $force = false)
    {
        !$force && $this->app->async && $this->app->throwIfRunning("Adding event listners while app is running is forbidden. You could use `Providers` to add event listners");

        if (is_string($listner) && !in_array(Runnable::class, class_implements($listner))) {
            throw new SystemError("`$listner` does not implement " . Runnable::class);
        }

        // Empty - initialize
        if (!isset($this->listners[$event])) {
            $this->listners[$event] = [];
        }
        // Limit reached - remove earliest
        else if (count($this->listners[$event]) >= self::MAX_EVENT_LISTENERS) {
            array_shift($this->listners[$event]);
        }

        $this->listners[$event][] = $listner;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEventListners(string $event): array
    {
        return $this->listners[$event] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function dispatchEvent(string $event, array $data = [])
    {
        if (!empty($this->getEventListners($event))) {
            async(new EventTask($event, $data));
        }
    }
}
