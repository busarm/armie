<?php

namespace Armie\Test\TestApp\Services;

use Armie\Interfaces\SingletonStatelessInterface;
use Armie\Traits\SingletonStateless;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class MockStatelessService implements SingletonStatelessInterface
{
    use SingletonStateless;
    public function __construct(public $id)
    {
    }
}
