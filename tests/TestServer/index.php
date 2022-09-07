<?php

use Busarm\PhpMini\Server;
use Busarm\PhpMini\Test\ServerTest;

require __DIR__ . '/../bootstrap.php';

$server = (new Server())
    ->addRoutePath('v1', __DIR__ . '/../TestApp')
    ->addDomainPath('localhost:' . ServerTest::HTTP_TEST_PORT, __DIR__ . '/../TestApp');
$server->run();
