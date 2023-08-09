<?php

namespace Busarm\PhpMini\Test\TestApp\Controllers;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class HomeTestController
{
    public function __construct(private App $app)
    {
    }

    public function ping()
    {
        return 'success-' . $this->app->env->value;
    }

    public function pingHtml()
    {
        return 'success-' . $this->app->env->value;
    }
}
