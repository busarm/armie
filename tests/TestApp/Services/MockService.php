<?php

namespace Busarm\PhpMini\Test\TestApp\Services;

use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Traits\Singleton;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class MockService implements SingletonInterface
{
    use Singleton;
    public function __construct(public $id)
    {
    }
}
