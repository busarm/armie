<?php

namespace Busarm\PhpMini\Test\TestApp\Services;

use Busarm\PhpMini\Interfaces\SingletonStatelessInterface;
use Busarm\PhpMini\Traits\SingletonStateless;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class MockStatelessService implements SingletonStatelessInterface
{
    use SingletonStateless;
    public function __construct(public $id)
    {
    }
}
