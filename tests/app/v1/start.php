<?php

use Armie\App;
use Armie\Bags\FileStore;
use Armie\Config;
use Armie\Configs\HttpConfig;
use Armie\Configs\PDOConfig;
use Armie\Configs\ServerConfig;
use Armie\Dto\CollectionBaseDto;
use Armie\Enums\Cron;
use Armie\Enums\Env;
use Armie\Enums\HttpMethod;
use Armie\Enums\Looper;
use Armie\Interfaces\ProviderInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Promise;
use Armie\Response;
use Armie\Service\LocalServiceDiscovery;
use Armie\Service\ServiceRegistryProvider;
use Armie\Tests\App\V1\Controllers\AuthTestController;
use Armie\Tests\App\V1\Controllers\HomeTestController;
use Armie\Tests\App\V1\Controllers\MessengerSocketController;
use Armie\Tests\App\V1\Controllers\ProductTestController;
use Armie\Tests\App\V1\Models\ProductTestModel;
use Armie\Tests\App\V1\Views\TestViewPage;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Workerman\Connection\ConnectionInterface;

use function Armie\Helpers\async;
use function Armie\Helpers\await;
use function Armie\Helpers\concurrent;
use function Armie\Helpers\dispatch;
use function Armie\Helpers\enqueue;
use function Armie\Helpers\env;
use function Armie\Helpers\listen;
use function Armie\Helpers\log_debug;

require __DIR__ . '/../../../vendor/autoload.php';

$config = (new Config('TestApp', '1'))
    ->setAppPath(__DIR__)
    ->setViewPath('Views')
    ->setSecret('ds3d5Posdf@nZods!mfo')
    ->setCookieEncrypt(true)
    ->setHttp((new HttpConfig())
        ->setCheckCors(true)
        ->setAllowAnyCorsDomain(true)
        ->setAllowedCorsHeaders(['*'])
        ->setAllowedCorsMethods(['GET']))
    ->setLogRequest(false)
    ->setSessionEnabled(true)
    ->setSessionLifetime(60)
    ->setDb(
        (new PDOConfig())
            ->setConnectionDriver('mysql')
            ->setConnectionHost('127.0.0.1')
            ->setConnectionDatabase('default')
            ->setConnectionPort(3310)
            ->setConnectionUsername('root')
            ->setConnectionPassword('root')
            ->setConnectionPersist(true)
            ->setConnectionErrorMode(true)
            ->setConnectionPoolSize(10)
    );

$app = new App($config, Env::parse(env('ENV')));
$app->addBinding(ProviderInterface::class, ServiceRegistryProvider::class);
$app->addProvider(new ServiceRegistryProvider(new FileStore($config->tempPath . '/store')));
$app->setServiceDiscovery($discovery ?? new LocalServiceDiscovery([]));

$app->get('ping')->to(HomeTestController::class, 'ping');
$app->get('pingHtml')->call(function (App $app) {
    return 'success-' . $app->env->value;
});
$app->get('auth/test')->to(AuthTestController::class, 'test');
$app->get('test/view-old/{name}')->call(function (RequestInterface $req, string $name) {
    return new TestViewPage($req, $name);
});
$app->get('test/view/{name}')->view(TestViewPage::class);
$app->get('test')->call(function (RequestInterface $req, App $app) {
    $req->cookie()->set('TestCookie', 'test', 30);

    return [
        'name'          => 'v1',
        'discovery'     => $app->serviceDiscovery?->getServiceClientsMap(),
        'headers'       => $req->header()->all(),
        'server'        => $req->server()->all(),
        'cookies'       => $req->cookie()->all(),
        'currentUrl'    => $req->currentUrl(),
        'baseUrl'       => $req->baseUrl(),
        'ip'            => $req->ip(),
        'requestId'     => $req->requestId(),
        'correlationId' => $req->correlationId(),
    ];
});
$app->get('test/queue')->call(function () {
    enqueue(function () {
        log_debug('1 - Processing queue');

        return ProductTestModel::update(1, [
            'name' => md5(uniqid()),
        ]);
    });
    enqueue(function () {
        log_debug('2 - Processing queue');

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
});

$app->get('test/promise')->call(function (App $app, RequestInterface $request) {
    $count = ConnectionInterface::$statistics['total_request'];
    $promise = (new Promise(function () use ($count) {
        log_debug('Processing promise db - count: ' . $count);

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    }));
    $promise->then(function (ProductTestModel $data) {
        log_debug('1 - Result of promise db - ' . $data?->get('name'));
        $data->set('name', 'hhahahahah');

        return $data;
    });
    $promise->then(function (ProductTestModel $data) {
        log_debug('2 - Result of promise db - ' . $data?->get('name'));
    });
    $waited = await($promise);
    log_debug('Promise completed - ' . $promise->done() . ' - ' . $waited?->get('name'));
});

listen(ProductTestModel::class, function ($data) {
    log_debug('Product event 1', $data);
});
listen(ProductTestModel::class, function ($data) {
    log_debug('Product event 2', $data);
});
listen(ProductTestModel::class, function ($data) {
    log_debug('Product event 3', $data);
});
listen(ProductTestModel::class, function ($data) {
    log_debug('Product event 4', $data);
});
$app->get('test/event')->call(function () {
    listen(ProductTestModel::class, function ($data) {
        log_debug('Product event 5 (running)', $data);
    });
    dispatch(ProductTestModel::class, ProductTestModel::findById(2)?->toArray() ?? []);
});
$app->get('test/db')->call(function () {
    return ProductTestModel::update(2, [
        'name' => md5(uniqid()),
    ]);
});
$app->get('test/db-class-async')->to(ProductTestController::class, 'dbAsync');
$app->get('test/db-class')->to(ProductTestController::class, 'db');
$app->get('test/db-async')->call(function () {
    async(function () {
        log_debug('1 - Processing Db async');

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    async(function () {
        log_debug('2 - Processing Db async');

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    async(function () {
        log_debug('3 - Processing Db async');

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    async(function () {
        log_debug('4 - Processing Db async');

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    async(function () {
        log_debug('5 - Processing Db async');

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
    async(function () {
        log_debug('6 - Processing Db async');

        return ProductTestModel::update(2, [
            'name' => md5(uniqid()),
        ]);
    });
});
$app->get('test/async-class')->to(ProductTestController::class, 'task');
$app->get('test/async-list')->call(function () {
    log_debug("Script cached - ", opcache_is_script_cached(__FILE__));
    $res = concurrent([
        function () {
            ProductTestModel::update(1, [
                'name' => md5(uniqid()),
            ]);
            $data = ProductTestModel::findById(1);
            log_debug('1 Non-wait async success ');

            return $data;
        },
        function () {
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('2 Non-wait async success');

            return $data->at(1);
        },
        function () {
            ProductTestModel::update(2, [
                'name' => md5(uniqid()),
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('3 Non-wait async success');

            return $data->at(2);
        },
        function () {
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('4 Non-wait async success');

            return $data->at(3);
        },
        function () {
            ProductTestModel::update(2, [
                'name' => md5(uniqid()),
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('5 Non-wait async success');

            return $data->at(4);
        },
        function () {
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('6 Non-wait async success');

            return $data->at(5);
        },
        function () {
            ProductTestModel::update(2, [
                'name' => md5(uniqid()),
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('7 Non-wait async success');

            return $data->at(6);
        },
        function () {
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('8 Non-wait async success');

            return $data->at(7);
        },
        function () {
            ProductTestModel::update(2, [
                'name' => md5(uniqid()),
            ]);
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('9 Non-wait async success');

            return $data->at(8);
        },
        function () {
            $data = CollectionBaseDto::of(ProductTestModel::itterateAll(), ProductTestModel::class);
            log_debug('10 Non-wait async success');

            return $data->at(9);
        },
    ], true);
    log_debug('Completed');
    // foreach($res as $item) {
    //     log_debug('Item completed', $item);
    // }

    return $res;
});
$app->get('test/async')->call(function () {
    print_r(PHP_EOL . '0 Test async started');
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('1 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('2 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('3 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('5 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('6 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('7 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('8 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('9 Non-wait async success' . PHP_EOL);
    });
    async(function () {
        CollectionBaseDto::of(ProductTestModel::itterateAll());
        print_r('10 Non-wait async success' . PHP_EOL);
    });
});

$app->get('test/http')->call(function (RequestInterface $req, App $app) {
    $http = new Client([
        'timeout'  => 10000,
    ]);
    $http->requestAsync(
        HttpMethod::GET->value,
        'https://busarm.com/ping',
        [
            RequestOptions::VERIFY => false,
        ]
    )->then(function () {
        print_r('Test Http Success' . PHP_EOL);
    });

    return 'Test success';
});

$app->get('test/session')->call(function (RequestInterface $req, App $app) {
    $req->session()?->set('TestSession', 'test');
    $store = new FileStore($app->config->tempPath . '/files');
    $store->set('TestSession-File', ['test', $app->env]);

    return [
        'name'    => 'v1',
        'enabled' => $app->config->sessionEnabled,
        'session' => $req->session()?->all(),
        'cookies' => $req->cookie()->all(),
        'store'   => $store->all(),
    ];
});

$app->get('test/download')->call(function (Response $response) {
    return $response->downloadFile(__DIR__ . '../../../README.md', 'README.md', true);
});

$app->start(
    'localhost',
    8181,
    (new ServerConfig())
        ->setLooper(Looper::EV)
        ->setHttpWorkers(4)
        ->setTaskWorkers(2)
        ->addJob(function () {
            log_debug('Testing EVERY_MINUTE Cron Job');
        }, Cron::EVERY_MINUTE)
        ->addJob(function () {
            log_debug('Testing One-Time Job');
        }, new DateTime('+5 seconds'))
        ->addSocket(2222, MessengerSocketController::class)
);
