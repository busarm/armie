<?php

namespace Busarm\PhpMini\Test\TestApp\Controllers;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Test\TestApp\Attributes\AuthorizeTestAttr;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class AuthTestController
{
    #[AuthorizeTestAttr(key: "php112233445566")]
    public  function test()
    {
        return "authorized";
    }
}
