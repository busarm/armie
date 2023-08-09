<?php

/**
 * @var \Psr\Http\Message\ServerRequestInterface|null $request Capture Server request
 * @var \Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface|null $discovery Capture Service discovery
 */

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Configs\HttpConfig;
use Busarm\PhpMini\Configs\PDOConfig;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Middlewares\SessionMiddleware;
use Busarm\PhpMini\Middlewares\StatelessSessionMiddleware;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Response;
use Busarm\PhpMini\Service\LocalServiceDiscovery;
use Busarm\PhpMini\Test\TestApp\Controllers\AuthTestController;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;
use Busarm\PhpMini\Test\TestApp\Models\CategoryTestModel;
use Busarm\PhpMini\Test\TestApp\Models\ProductTestModel;
use Faker\Factory;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setSecret("ds3d5Posdf@nZods!mfo")
    ->setCookieEncrypt(false)
    ->setSessionEnabled(false)
    ->setHttp((new HttpConfig)
        ->setCheckCors(true)
        ->setAllowAnyCorsDomain(true)
        ->setAllowedCorsHeaders(['*'])
        ->setAllowedCorsMethods(['GET']))
    ->setDb((new PDOConfig)
        ->setConnectionDriver("mysql")
        ->setConnectionHost("mysql")
        ->setConnectionDatabase('default')
        ->setConnectionPort(3306)
        ->setConnectionUsername("root")
        ->setConnectionPassword("root")
        ->setConnectionPersist(false)
        ->setConnectionErrorMode(true));

$app = new App($config, Env::LOCAL);
$app->setServiceDiscovery($discovery ?? new LocalServiceDiscovery([]));

$app->get('ping')->to(HomeTestController::class, 'ping');
$app->get('pingHtml')->call(function (App $app) {
    return 'success-' . $app->env->value;
});
$app->get('auth/test')->to(AuthTestController::class, 'test');
$app->get('test')->call(function (RequestInterface $req, App $app) {
    $req->session()->set("Name", "Samuel");
    return [
        'name' => 'v1',
        'discovery' => $app->serviceDiscovery?->getServiceClientsMap(),
        'headers' => $req->header()->all(),
        'server' => $req->server()->all(),
        'cookies' => $req->cookie()->all(),
        'session' => $req->session()?->all(),
        'currentUrl' => $req->currentUrl(),
        'baseUrl' => $req->baseUrl(),
        'ip' => $req->ip(),
        'correlationId' => $req->correlationId(),
    ];
});

$app->get('test-db')->call(function () {
    return CollectionBaseDto::of(ProductTestModel::getAll());
});

$app->get('test-db-write')->call(function () {
    $faker = Factory::create();
    $cat = CategoryTestModel::create(['name' => $faker->word(), 'description' => $faker->sentence()]);
    $products = [];
    foreach (range(1, 10) as $_) {
        $products[] = ProductTestModel::create([
            'name' =>  $faker->name(),
            'type' => $faker->word(),
            'qty' => $faker->numberBetween(1, 1000),
            'categoryId' => $cat->get('id')
        ]);
    }
    return CollectionBaseDto::of($products);
});

$app->get('download')->call(function (Response $response) {
    return $response->downloadFile(__DIR__ . '../../../README.md', 'README.md', true);
});

return $app->run(Request::capture($request ?? null, $config))->send($config->http->sendAndContinue);
