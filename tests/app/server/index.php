<?php

use Armie\Server;
use Armie\Service\LocalClient;
use Armie\Service\LocalServiceDiscovery;

require __DIR__ . '/../../vendor/autoload.php';

$server = (new Server('Busarm Test Server'))
    // ->addServiceDiscovery(new LocalServiceDiscovery([
    //     new RemoteClient('v1',  "http://v1"),
    //     new RemoteClient('v2',  "http://v2"),
    //     new RemoteClient('v3',  "http://v3"),
    // ]));
    ->addServiceDiscovery(
        new LocalServiceDiscovery([
            new LocalClient('v1', __DIR__ . '/../v1'),
            new LocalClient('v2', __DIR__ . '/../v2'),
            new LocalClient('v3', __DIR__ . '/../v3'),
        ]),
        true
    );

echo $server->run()->getBody();
