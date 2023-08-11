<?php

namespace Busarm\PhpMini\Tasks;

use Busarm\PhpMini\Interfaces\Runnable;

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class EventTask extends Task
{

    public function __construct(protected string $event, protected array $data = [])
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $listners = app()->eventManager->getEventListners($this->event);
        if ($listners) {
            foreach ($listners as $listner) {
                if (is_callable($listner)) {
                    call_user_func($listner, $this->data);
                } else {
                    $task = app()->di->instantiate($listner, null, $this->data);
                    if ($task instanceof Runnable) {
                        $task->run();
                    }
                }
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        return [
            'event' => $this->event,
            'data' => $this->data,
        ];
    }

    /**
     * Implementation of magic method __invoke()
     */
    public function __invoke()
    {
        return $this->run();
    }
}
