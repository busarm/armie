<?php

namespace Armie\Test\TestApp\Services;

use Armie\Interfaces\SingletonInterface;
use Armie\Traits\Singleton;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class MockService implements SingletonInterface
{
    use Singleton;

    public function __construct(public $id)
    {
    }
}
