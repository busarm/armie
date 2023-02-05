<?php

use Busarm\PhpMini\Server;
use Busarm\PhpMini\Service\LocalClient;
use Busarm\PhpMini\Service\RemoteClient;
use Busarm\PhpMini\Test\ServerTest;

require __DIR__ . '/../../vendor/autoload.php';

$server = (new Server("Busarm Test Server"))
    ->addRoutePath('v1', __DIR__ . '/../TestApp')
    ->addRouteService('v2', new LocalClient('MyTestAppV2',  __DIR__ . '/../TestAppV2'))
    ->addRouteService('v3', new LocalClient('MyTestAppV3',  __DIR__ . '/../TestAppV3'))
    ->addRouteService('v3-remote', new RemoteClient('MyTestAppV3Remote',  ServerTest::HTTP_TEST_URL . ":" . ServerTest::HTTP_TEST_PORT . '/v3/test'))
    ->addRouteService('v4', new RemoteClient('MyTestAppV4',  "https://staging.busarm.com/home/ping"));
$server->run()?->send();
