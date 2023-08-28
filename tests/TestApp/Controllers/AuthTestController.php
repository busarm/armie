<?php

namespace Armie\Test\TestApp\Controllers;

use Armie\Test\TestApp\Attributes\AuthorizeTestAttr;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class AuthTestController
{
    #[AuthorizeTestAttr(key: 'php112233445566')]
    public function test()
    {
        return 'authorized';
    }
}
