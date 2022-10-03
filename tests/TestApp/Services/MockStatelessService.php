<?php

namespace Busarm\PhpMini\Test\TestApp\Services;

use Busarm\PhpMini\Interfaces\SingletonStatelessInterface;
use Busarm\PhpMini\Traits\SingletonStateless;

class MockStatelessService implements SingletonStatelessInterface
{
    use SingletonStateless;
    public function __construct(public $id)
    {
    }
}
