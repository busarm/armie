<?php

/**
 * @var \Psr\Http\Message\ServerRequestInterface|null    $request Capture Server request
 * @var \Armie\Interfaces\ServiceDiscoveryInterface|null $discovery Capture Service discovery
 */

use Armie\App;
use Armie\Config;
use Armie\Configs\HttpConfig;
use Armie\Configs\PDOConfig;
use Armie\Dto\CollectionBaseDto;
use Armie\Enums\Env;
use Armie\Interfaces\RequestInterface;
use Armie\Request;
use Armie\Response;
use Armie\Service\LocalServiceDiscovery;
use Armie\Test\TestApp\Controllers\AuthTestController;
use Armie\Test\TestApp\Controllers\HomeTestController;
use Armie\Test\TestApp\Models\CategoryTestModel;
use Armie\Test\TestApp\Models\ProductTestModel;
use Faker\Factory;

use function Armie\Helpers\async;
use function Armie\Helpers\dispatch;
use function Armie\Helpers\enqueue;
use function Armie\Helpers\listen;
use function Armie\Helpers\log_debug;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setSecret('ds3d5Posdf@nZods!mfo')
    ->setCookieEncrypt(false)
    ->setSessionEnabled(false)
    ->setHttp((new HttpConfig())
        ->setCheckCors(true)
        ->setAllowAnyCorsDomain(true)
        ->setSendAndContinue(true)
        ->setAllowedCorsHeaders(['*'])
        ->setAllowedCorsMethods(['GET']))
    ->setDb((new PDOConfig())
        ->setConnectionDriver('mysql')
        ->setConnectionHost('mysql')
        ->setConnectionDatabase('default')
        ->setConnectionPort(3306)
        ->setConnectionUsername('root')
        ->setConnectionPassword('root')
        ->setConnectionPersist(true)
        ->setConnectionErrorMode(true));

$app = new App($config, Env::LOCAL);

$app->setServiceDiscovery($discovery ?? new LocalServiceDiscovery([]));

$app->get('ping')->to(HomeTestController::class, 'ping');
$app->get('pingHtml')->call(function (App $app) {
    return 'success-' . $app->env->value;
});
$app->get('auth/test')->to(AuthTestController::class, 'test');
$app->get('test')->call(function (RequestInterface $req, App $app) {
    $req->session()?->set('Name', 'Samuel');

    return [
        'name'          => 'v1',
        'discovery'     => $app->serviceDiscovery?->getServiceClientsMap(),
        'headers'       => $req->header()->all(),
        'server'        => $req->server()->all(),
        'cookies'       => $req->cookie()->all(),
        'session'       => $req->session()?->all(),
        'currentUrl'    => $req->currentUrl(),
        'baseUrl'       => $req->baseUrl(),
        'ip'            => $req->ip(),
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
            'name'       => $faker->name(),
            'type'       => $faker->word(),
            'qty'        => $faker->numberBetween(1, 1000),
            'categoryId' => $cat->get('id'),
        ]);
    }

    return CollectionBaseDto::of($products);
});

$app->get('test/async')->call(function () {
    async(function () {
        sleep(5);
        $data = CollectionBaseDto::of(ProductTestModel::getAll());
        log_debug('1 Async success' . PHP_EOL);

        return $data->at(1);
    });
    async(function () {
        sleep(5);
        $data = CollectionBaseDto::of(ProductTestModel::getAll());
        log_debug('2 Async success' . PHP_EOL);

        return $data->at(2);
    });
    async(function () {
        sleep(5);
        $data = CollectionBaseDto::of(ProductTestModel::getAll());
        log_debug('3 Async success' . PHP_EOL);

        return $data->at(3);
    });
    async(function () {
        $data = CollectionBaseDto::of(ProductTestModel::getAll());
        log_debug('4 Async success' . PHP_EOL);

        return $data->at(4);
    });
});

listen(ProductTestModel::class, function ($data) {
    sleep(5);
    log_debug('Product event list. 1 (before start)', $data);
});
$app->get('test/event')->call(function () {
    listen(ProductTestModel::EVENT_AFTER_QUERY, function ($data) {
        sleep(10);
        log_debug('Product event list. 2 (running)', $data);
    });
    dispatch(ProductTestModel::class, ProductTestModel::findById(2)?->toArray() ?? []);
});

$app->get('test/queue')->call(function () {
    enqueue(function () {
        log_debug('1 - Processing queue');
        sleep(5);

        return ProductTestModel::update(1, [
            'name' => md5(uniqid()),
        ]);
    });
    enqueue(function () {
        log_debug('2 - Processing queue');
        sleep(5);

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    enqueue(function () {
        log_debug('3 - Processing queue');
        sleep(5);

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    enqueue(function () {
        log_debug('4 - Processing queue');
        sleep(5);

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    enqueue(function () {
        log_debug('5 - Processing queue');
        sleep(5);

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
});

$app->get('download')->call(function (Response $response) {
    return $response->downloadFile(__DIR__ . '../../../README.md', 'README.md', true);
});

return $app->run(Request::capture($request ?? null, $config))->send($config->http->sendAndContinue);
