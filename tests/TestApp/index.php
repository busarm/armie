<?php

/**
 * @var Busarm\PhpMini\Interfaces\RequestInterface $request Capture Server request
 */

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Route;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setConfigPath('Configs')
    ->setViewPath('Views')
    ->setEncryptionKey("ds3d5Posdf@nZods!mfo")
    ->setCookieEncrypt(true)
    ->setHttpSessionAutoStart(true);
$app = new App($config);
$app->router->addRoutes([
    Route::get('ping')->to(HomeTestController::class, 'ping'),
    Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
]);
return $app->run($request ?? null);
