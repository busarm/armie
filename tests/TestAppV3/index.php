<?php

/**
 * @var \Psr\Http\Message\ServerRequestInterface|null $request Capture Server request
 * @var \Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface|null $discovery Capture Service discovery
 */

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Configs\HttpConfig;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Service\RemoteServiceDiscovery;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setEncryptionKey("asdgkasdfer@jsfrtv453ds!mfo")
    ->setCookieEncrypt(false)
    ->setHttp((new HttpConfig)
        ->setCheckCors(true)
        ->setAllowAnyCorsDomain(true)
        ->setAllowedCorsHeaders(['*'])
        ->setAllowedCorsMethods(['GET']));
$app = new App($config);
$app->setServiceDiscovery($discovery ?? new RemoteServiceDiscovery('https://server/discover'));

$app->get('ping')->call(function (App $app) {
    return 'success-v3-' . $app->env;
});

$app->get('test')->call(function (RequestInterface $req, App $app) {
    return [
        'name' => 'v3',
        'discovery' => $app->serviceDiscovery?->getServiceClientsMap(),
        'headers' => $req->header()->all(),
        'server' => $req->server()->all(),
        'cookies' => $req->cookie()->all(),
    ];
});
return $app->run(Request::capture($request ?? null, $config))->send($config->http->sendAndContinue);
