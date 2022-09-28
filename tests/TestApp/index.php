<?php

/**
 * @var Busarm\PhpMini\Interfaces\RequestInterface $request Capture Server request
 */

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setEncryptionKey("ds3d5Posdf@nZods!mfo")
    ->setCookieEncrypt(false)
    ->setHttpSessionAutoStart(false);
$app = new App($config);
$app->get('ping')->to(HomeTestController::class, 'ping');
$app->get('pingHtml')->call(function (App $app) {
    return 'success-callable-'.$app->env;
});
return $app->run($request ?? null);
