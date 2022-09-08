<?php

namespace Busarm\PhpMini\Test\TestApp\Controllers;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\ResponseInterface;

use function Busarm\PhpMini\Helpers\log_debug;

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
        log_debug(HomeTestController::class . ' started - ' . $app->env);
    }

    public function ping()
    {
        return 'success';
    }

    public function pingHtml(ResponseInterface $response)
    {
        return $response->html('success', 200, false);
    }
}
