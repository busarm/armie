<?php

use Busarm\PhpMini\Server;
use Busarm\PhpMini\Service\LocalServiceDiscovery;
use Busarm\PhpMini\Service\RemoteClient;

require __DIR__ . '/../../vendor/autoload.php';

$server = (new Server("Busarm Test Server"))
    ->addServiceDiscovery(new LocalServiceDiscovery([
        new RemoteClient('v1',  "http://v1"),
        new RemoteClient('v2',  "http://v2"),
        new RemoteClient('v3',  "http://v3"),
    ]), true);
$server->run()?->send();
