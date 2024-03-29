<?php

namespace Armie\Tasks;

use Closure;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class CallableTask extends Task
{
    public function __construct(protected Closure $callable, protected array $data = [])
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (is_callable($this->callable)) {
            if (array_is_list($this->data)) {
                $result = call_user_func($this->callable, ...$this->data);
            } else {
                $result = call_user_func($this->callable, $this->data);
            }

            return $result !== false ? $result : null;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        return [
            'callable' => $this->callable,
            'data'     => $this->data,
        ];
    }
}
