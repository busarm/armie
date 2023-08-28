<?php

namespace Armie\Test\TestApp\Controllers;

use Armie\App;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class HomeTestController
{
    public function __construct(private App $app)
    {
    }

    public function ping()
    {
        return 'success-'.$this->app->env->value;
    }

    public function pingHtml()
    {
        return 'success-'.$this->app->env->value;
    }
}
