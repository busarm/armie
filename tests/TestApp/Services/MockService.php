<?php

namespace Busarm\PhpMini\Test\TestApp\Services;

use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Traits\Singleton;

class MockService implements SingletonInterface
{
    use Singleton;
    public function __construct(public $id)
    {
    }
}
