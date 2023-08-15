<?php

use Busarm\PhpMini\App;
use Busarm\PhpMini\Async;
use Busarm\PhpMini\Bags\FileStore;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Configs\HttpConfig;
use Busarm\PhpMini\Configs\PDOConfig;
use Busarm\PhpMini\Configs\WorkerConfig;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Enums\Cron;
use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\Looper;
use Busarm\PhpMini\Interfaces\ProviderInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Promise;
use Busarm\PhpMini\Response;
use Busarm\PhpMini\Service\LocalServiceDiscovery;
use Busarm\PhpMini\Service\ServiceRegistryProvider;
use Busarm\PhpMini\Tasks\CallableTask;
use Busarm\PhpMini\Test\TestApp\Controllers\AuthTestController;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;
use Busarm\PhpMini\Test\TestApp\Controllers\ProductTestController;
use Busarm\PhpMini\Test\TestApp\Models\ProductTestModel;
use Busarm\PhpMini\Test\TestApp\Views\TestViewPage;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Workerman\Connection\ConnectionInterface;
use Workerman\Timer;

use function Busarm\PhpMini\Helpers\async;
use function Busarm\PhpMini\Helpers\await;
use function Busarm\PhpMini\Helpers\dispatch;
use function Busarm\PhpMini\Helpers\listen;
use function Busarm\PhpMini\Helpers\log_debug;
use function Busarm\PhpMini\Helpers\concurrent;
use function Busarm\PhpMini\Helpers\enqueue;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config)
    ->setAppPath(__DIR__)
    ->setViewPath('Views')
    ->setSecret("ds3d5Posdf@nZods!mfo")
    ->setCookieEncrypt(true)
    ->setHttp((new HttpConfig)
        ->setCheckCors(true)
        ->setAllowAnyCorsDomain(true)
        ->setAllowedCorsHeaders(['*'])
        ->setAllowedCorsMethods(['GET']))
    ->setLogRequest(false)
    ->setSessionEnabled(true)
    ->setSessionLifetime(60)
    ->setDb((new PDOConfig)
            ->setConnectionDriver("mysql")
            ->setConnectionHost("127.0.0.1")
            ->setConnectionDatabase('default')
            ->setConnectionPort(3310)
            ->setConnectionUsername("root")
            ->setConnectionPassword("root")
            ->setConnectionPersist(true)
            ->setConnectionErrorMode(true)
            ->setConnectionPoolSize(10)
    );

$app = new App($config, Env::LOCAL);
$app->addBinding(ProviderInterface::class, ServiceRegistryProvider::class);
$app->addProvider(new ServiceRegistryProvider(new FileStore($config->tempPath . '/store')));
$app->setServiceDiscovery($discovery ?? new LocalServiceDiscovery([]));

$app->get('ping')->to(HomeTestController::class, 'ping');
$app->get('pingHtml')->call(function (App $app) {
    return 'success-' . $app->env->value;
});
$app->get('auth/test')->to(AuthTestController::class, 'test');
$app->get('test/view/{name}')->call(function (RequestInterface $req, string $name) {
    return new TestViewPage($req, $name);
});
$app->get('test/view')->call(function (RequestInterface $req) {
    return new TestViewPage($req, "NO-NAME");
});
$app->get('test')->call(function (RequestInterface $req, App $app) {
    $req->cookie()->set("TestCookie", "test", 30);

    return [
        'name' => 'v1',
        'discovery' => $app->serviceDiscovery?->getServiceClientsMap(),
        'headers' => $req->header()->all(),
        'server' => $req->server()->all(),
        'cookies' => $req->cookie()->all(),
        'currentUrl' => $req->currentUrl(),
        'baseUrl' => $req->baseUrl(),
        'ip' => $req->ip(),
        'requestId' => $req->requestId(),
        'correlationId' => $req->correlationId(),
    ];
});
$app->get('test/queue')->call(function () {
    enqueue(function () {
        log_debug("1 - Processing queue");
        return ProductTestModel::update(1, [
            'name' =>  md5(uniqid())
        ]);
    });
    enqueue(function () {
        log_debug("2 - Processing queue");
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    });
});
$app->get('test/promise')->call(function (App $app, RequestInterface $request) {
    $count = ConnectionInterface::$statistics['total_request'];
    $promise = (new Promise(function () use ($count) {
        log_debug("1 - Processing promise db - " . $count);
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    }));
    $promise->then(function (ProductTestModel $data) {
        log_debug("1 - Result of promise db - ", $data?->get('name'));
    });
    return $promise->getId();
});

listen(ProductTestModel::class, function ($data) {
    log_debug("Product event 1", $data);
});
listen(ProductTestModel::class, function ($data) {
    log_debug("Product event 2", $data);
});
listen(ProductTestModel::class, function ($data) {
    log_debug("Product event 3", $data);
});
listen(ProductTestModel::class, function ($data) {
    log_debug("Product event 4", $data);
});
$app->get('test/event')->call(function () {
    listen(ProductTestModel::class, function ($data) {
        log_debug("Product event 5 (running)", $data);
    });
    dispatch(ProductTestModel::class, ProductTestModel::findById(2)?->toArray() ?? []);
});
$app->get('test/db')->call(function () {
    return ProductTestModel::update(2, [
        'name' =>  md5(uniqid())
    ]);
});
$app->get('test/db-class-async')->to(ProductTestController::class, 'dbAsync');
$app->get('test/db-class')->to(ProductTestController::class, 'db');
$app->get('test/db-async')->call(function () {
    async(function () {
        log_debug("1 - Processing Db async");
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    });
    async(function () {
        log_debug("2 - Processing Db async");
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    });
    async(function () {
        log_debug("3 - Processing Db async");
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    });
    async(function () {
        log_debug("4 - Processing Db async");
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    });
    async(function () {
        log_debug("5 - Processing Db async");
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    });
    async(function () {
        log_debug("6 - Processing Db async");
        return ProductTestModel::update(2, [
            'name' =>  md5(uniqid())
        ]);
    });
});
$app->get('test/async-class')->to(ProductTestController::class, 'task');
$app->get('test/async-list')->call(function () {
    $res = concurrent([
        (function () {
            ProductTestModel::update(2, [
                'name' =>  md5(uniqid())
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("1 Non-wait async success");
            return $data->at(0);
        }),
        (function () {
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("2 Non-wait async success");
            return $data->at(1);
        }),
        (function () {
            ProductTestModel::update(2, [
                'name' =>  md5(uniqid())
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("3 Non-wait async success");
            return $data->at(2);
        }),
        (function () {
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("4 Non-wait async success");
            return $data->at(3);
        }),
        (function () {
            ProductTestModel::update(2, [
                'name' =>  md5(uniqid())
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("5 Non-wait async success");
            return $data->at(4);
        }),
        (function () {
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("6 Non-wait async success");
            return $data->at(5);
        }),
        (function () {
            ProductTestModel::update(2, [
                'name' =>  md5(uniqid())
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("7 Non-wait async success");
            return $data->at(6);
        }),
        (function () {
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("8 Non-wait async success");
            return $data->at(7);
        }),
        (function () {
            ProductTestModel::update(2, [
                'name' =>  md5(uniqid())
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("9 Non-wait async success");
            return $data->at(8);
        }),
        (function () {
            $data = CollectionBaseDto::of(ProductTestModel::getAll(), ProductTestModel::class);
            log_debug("10 Non-wait async success");
            return $data->at(9);
        })
    ], true);
    log_debug("Completed");
    return $res;
});
$app->get('test/async')->call(function () {

    print_r(PHP_EOL . "0 Test async started");
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("1 Non-wait async success" . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("2 Non-wait async success" . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("3 Non-wait async success" . PHP_EOL);
    });
    $response = await(function () {
        $data = CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("4 Wait async success" . PHP_EOL);
        return $data->first();
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("5 Non-wait async success" . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("6 Non-wait async success" . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("7 Non-wait async success" . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("8 Non-wait async success" . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("9 Non-wait async success" . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::getAll());
        print_r("10 Non-wait async success" . PHP_EOL);
    });
    print_r("11 Test response from 4" . PHP_EOL);
    print_r($response);
    print_r(PHP_EOL);
    print_r("12 Test  async complete" . PHP_EOL);
});


$app->get('test/http')->call(function (RequestInterface $req, App $app) {
    $http = new Client([
        'timeout'  => 10000,
    ]);
    $http->requestAsync(
        HttpMethod::GET->value,
        "https://busarm.com/ping",
        [
            RequestOptions::VERIFY => false
        ]
    )->then(function () {
        print_r("Test Http Success" . PHP_EOL);
    });
    return "Test success";
});

$app->get('test/session')->call(function (RequestInterface $req, App $app) {
    $req->session()?->set("TestSession", "test");
    $store = new FileStore($app->config->tempPath . '/files');
    $store->set("TestSession-File", ["test", $app->env]);
    return [
        'name' => 'v1',
        'enabled' => $app->config->sessionEnabled,
        'session' => $req->session()?->all(),
        'cookies' => $req->cookie()->all(),
        'store' => $store->all()
    ];
});

$app->get('test/download')->call(function (Response $response) {
    return $response->downloadFile(__DIR__ . '../../../README.md', 'README.md', true);
});

$app->start(
    'localhost',
    8181,
    (new WorkerConfig)
        ->setLooper(Looper::SWOOLE)
        ->addJob(new CallableTask(function () {
            log_debug("Testing EVERY_MINUTE Cron Job");
        }), Cron::EVERY_MINUTE)
        ->addJob(new CallableTask(function () {
            log_debug("Testing One-Time Job");
        }), (new DateTime('+5 seconds')))
);
