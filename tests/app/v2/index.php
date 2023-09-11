<?php

/**
 * @var \Psr\Http\Message\ServerRequestInterface|null    $request Capture Server request
 * @var \Armie\Interfaces\ServiceDiscoveryInterface|null $discovery Capture Service discovery
 */

use Armie\App;
use Armie\Config;
use Armie\Configs\HttpConfig;
use Armie\Interfaces\RequestInterface;
use Armie\Request;
use Armie\Service\RemoteServiceDiscovery;

require __DIR__.'/../../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setSecret('asdgkasdfer@jsfrtv453ds!mfo')
    ->setCookieEncrypt(false)
    ->setHttp((new HttpConfig())
        ->setCheckCors(true)
        ->setAllowAnyCorsDomain(true)
        ->setAllowedCorsHeaders(['*'])
        ->setAllowedCorsMethods(['GET']));
$app = new App($config);
$app->setServiceDiscovery($discovery ?? new RemoteServiceDiscovery('https://server/discover'));

$app->get('ping')->call(function (App $app) {
    return 'success-v2-' . $app->env->value;
});

$app->get('test')->call(function (RequestInterface $req, App $app) {
    return [
        'name'      => 'v2',
        'discovery' => $app->serviceDiscovery?->getServiceClientsMap(),
        'headers'   => $req->header()->all(),
        'server'    => $req->server()->all(),
        'cookies'   => $req->cookie()->all(),
    ];
});

return $app->run(Request::capture($request ?? null, $config))->send($config->http->sendAndContinue);
