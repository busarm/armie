<?php

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Route;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;

require __DIR__ . '/../bootstrap.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setConfigPath('Configs')
    ->setViewPath('Views');
$app = new App($config);
$app->router->addRoutes([
    Route::get('ping')->to(HomeTestController::class, 'ping'),
    Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
]);
$app->run();
