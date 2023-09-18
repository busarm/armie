<?php

/**
 * @var \Psr\Http\Message\ServerRequestInterface|null    $request Capture Server request
 * @var \Armie\Interfaces\ServiceDiscoveryInterface|null $discovery Capture Service discovery
 */

use Armie\App;
use Armie\Config;
use Armie\Configs\HttpConfig;
use Armie\Configs\PDOConfig;
use Armie\Data\PDO\Relations\ManyToMany;
use Armie\Data\PDO\Relations\OneToOne;
use Armie\Dto\CollectionBaseDto;
use Armie\Enums\Env;
use Armie\Interfaces\RequestInterface;
use Armie\Request;
use Armie\Response;
use Armie\Service\LocalServiceDiscovery;
use Armie\Tests\App\V1\Controllers\AuthTestController;
use Armie\Tests\App\V1\Controllers\HomeTestController;
use Armie\Tests\App\V1\Models\CategoryTestModel;
use Armie\Tests\App\V1\Models\ProductTestModel;
use Faker\Factory;

use function Armie\Helpers\async;
use function Armie\Helpers\concurrent;
use function Armie\Helpers\dispatch;
use function Armie\Helpers\enqueue;
use function Armie\Helpers\listen;
use function Armie\Helpers\log_debug;
use function Armie\Helpers\log_warning;

require __DIR__ . '/../../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setSecret('ds3d5Posdf@nZods!mfo')
    ->setCookieEncrypt(false)
    ->setSessionEnabled(false)
    ->setLogRequest(true)
    ->setHttp((new HttpConfig())
        ->setCheckCors(true)
        ->setAllowAnyCorsDomain(true)
        ->setSendAndContinue(true)
        ->setAllowedCorsHeaders(['*'])
        ->setAllowedCorsMethods(['GET']))
    ->setDb((new PDOConfig())
        ->setConnectionDriver('mysql')
        ->setConnectionHost('localhost')
        ->setConnectionDatabase('default')
        ->setConnectionPort(3310)
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
$app->get('/')->to(HomeTestController::class, 'ping');
$app->get('auth/test')->to(AuthTestController::class, 'test');

$app->get('test')->call(function (RequestInterface $req, App $app) {
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
        'requestId' => $req->requestId(),
        'correlationId' => $req->correlationId(),
    ];
});

$app->get('test-db-rel')->call(function () {
    ProductTestModel::update(4, ['name' => md5((string)microtime(true))]);
    return ProductTestModel::withRelations()
        ->loadRelation('tags', function (ManyToMany $relation) {
            $relation->setMode(ManyToMany::MODE_ITEM);
            $relation->setColumns(['id', 'name']);
            $relation->setSort(['id' => 'desc']);
        })
        ->loadRelation('category', function (OneToOne $relation) {
            $relation->setColumns(['id']);
        })
        ->all(columns: ['id', 'name', 'qty'], conditions: ['<=' => ['qty' => 10]], limit: 10);
});

$app->get('test-async')->call(function () {
    $parallel = concurrent([
        (function () {
            log_warning("1-Promise processing");
            return ProductTestModel::withoutRelations()
                ->find(id: 3, columns: ['id', 'name', 'categoryId'])
                ?->loadRelation('tags', function (ManyToMany $relation) {
                    $relation->setMode(ManyToMany::MODE_ITEM);
                });
        }),
        (function () {
            log_warning("2-Promise processing");
            return ProductTestModel::findById(4);
        }),
        (function () {
            log_warning("3-Promise processing");
            return ProductTestModel::findById(4);
        }),
        (function () {
            log_warning("4-Promise processing");
            return ProductTestModel::findById(4);
        }),
        (function () {
            log_warning("5-Promise processing");
            return ProductTestModel::findById(4);
        }),
        (function () {
            log_warning("6-Promise processing");
            return ProductTestModel::update(4, ['name' => md5((string)microtime(true))]);
        }),
        (function () {
            log_warning("7-Promise processing");
            return ProductTestModel::update(4, ['name' => md5((string)microtime(true))]);
        }),
        (function () {
            log_warning("8-Promise processing");
            return ProductTestModel::update(4, ['name' => md5((string)microtime(true))]);
        }),
        (function () {
            log_warning("9-Promise processing");
            return ProductTestModel::update(4, ['name' => md5((string)microtime(true))]);
        }),
        (function () {
            log_warning("10-Promise processing");
            return ProductTestModel::update(4, ['name' => md5((string)microtime(true))]);
        })
    ]);
    log_debug('Completed');
    return $parallel;
});

$app->get('test-db')->call(function () {
    return CollectionBaseDto::of(
        ProductTestModel::withRelations()
            ->all(
                columns: ['id', 'name', 'type'],
                conditions: [
                    '>=' => ['createdAt' => "2023-07-16", 'updatedAt' => "2023-07-17"],
                    '<=' => ['qty' => 1000]
                ],
                sort: ["createdAt"]
            )
    );
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
