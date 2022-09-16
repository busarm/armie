<?php

namespace Busarm\PhpMini\Errors;

use Busarm\PhpMini\App;
use Error;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class SystemError extends Error
{

    /**
     * Error handler
     * 
     * @param App $app
     * @return void
     */
    public function handler(App $app)
    {
        $app->reporter->reportException($this);
        $trace = array_map(function ($instance) {
            return [
                'file' => $instance['file'] ?? null,
                'line' => $instance['line'] ?? null,
                'class' => $instance['class'] ?? null,
                'function' => $instance['function'] ?? null,
            ];
        }, $this->getTrace());
        $app->showMessage(500, $this->getMessage(), $this->getCode(), $this->getLine(), $this->getFile(), $trace);
    }
}
