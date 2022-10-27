<?php

/**
 * @var Busarm\PhpMini\Interfaces\RequestInterface|null $request Capture Server request
 */

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Middlewares\CorsMiddleware;
use Busarm\PhpMini\Response;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;
use Middlewares\Firewall;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setEncryptionKey("ds3d5Posdf@nZods!mfo")
    ->setCookieEncrypt(false)
    ->setHttpCheckCors(true)
    ->setHttpAllowAnyCorsDomain(true)
    ->setHttpAllowedCorsHeaders(['*'])
    ->setHttpAllowedCorsMethods(['GET'])
    ->setHttpSessionAutoStart(false);
$app = new App($config);
$app->addMiddleware(new CorsMiddleware());
$app->addMiddleware((new Firewall(['::1', '0.0.0.0', '127.0.0.1', '192.168.*']))
    ->blacklist([
        '192.168.0.1',
        '192.168.1.1',
    ]));

$app->get('ping')->to(HomeTestController::class, 'ping');
$app->get('pingHtml')->call(function (App $app) {
    return 'success-callable-' . $app->env;
});
$app->get('download')->call(function (Response $response) {
    return $response->download(fopen(__DIR__ . '../../../README.md', 'rb'), 'README.md', false);
});
return $app->run($request ?? null);
