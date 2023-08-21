<p align="center">
<img src="https://github.com/busarm/armie/assets/25706510/ca6aed95-7931-45de-afca-4d8f9b1498a7" alt="Armie_Logo_256px"/>
</p>
<p align="center">
<a href="https://github.com/busarm/armie/actions/workflows/php.yml"><img src="https://github.com/busarm/armie/actions/workflows/php.yml/badge.svg?branch=master" alt="Test"/></a>
<a href="https://packagist.org/packages/busarm/armie"><img src="https://poser.pugx.org/busarm/armie/license" alt="Test"/></a>
<a href="https://packagist.org/packages/busarm/armie"><img src="https://poser.pugx.org/busarm/armie/v" alt="Latest Stable Version"/></a>
<a href="https://packagist.org/packages/busarm/armie"><img src="https://poser.pugx.org/busarm/armie/require/php" alt="PHP Version Require"/></a>
</p>

## Introduction

An elegant PHP framework designed to provide high performance with optimal developer experience. It includes support for different architecture patterns:

- Model-View-Controller (MVC)
- Service-oriented
- Microservices
- Event Driven
- Asynchronous Queuing

## Installation

`composer require busarm/armie`

## Usage

### Traditional HTTP Server

Traditional HTTP server using PHP-FPM and NGINX or Apache.

#### Single Application

Run a single application

```php

    # ../myapp/public/index.php

    define('APP_START_TIME', floor(microtime(true) * 1000));
    require __DIR__ . '/../vendor/autoload.php';

    $config = (new Config())
            ->setAppPath(dirname(__DIR__))
            ->setConfigPath('Configs')
            ->setViewPath('Views');
    $app = new App($config);

    $app->get('/product/{id}')->to(ProductController::class, 'get');

    $app->run();
```

#### Multi Tenant Application

Host multiple applications or modules. Supports path and domain routing

```php

    # ../index.php
    require __DIR__ . '/../vendor/autoload.php';

    $server = (new Server())
        // Use `myapp` for requests with path `v1/....`
        ->addRoutePath('v1', __DIR__ . '/myapp/public')
        // Use `mydevapp` for requests with domain name `dev.myapp.com`
        ->addDomainPath('dev.myapp.com', __DIR__ . '/mydevapp/public');
    $server->run();


    # ../myapp/public/index.php

    /**
     * @var \Psr\Http\Message\ServerRequestInterface|null $request Capture Server request
     * @var \Armie\Interfaces\ServiceDiscoveryInterface|null $discovery Capture Service discovery
     */

    require __DIR__ . '/../vendor/autoload.php';

    $config = (new Config())
        ->setAppPath(dirname(__DIR__))
        ->setConfigPath('Configs')
        ->setViewPath('Views');
    $app = new App($config);
    $app->setServiceDiscovery($discovery ?? new LocalServiceDiscovery([]));

    $app->get('/product/{id}')->to(ProductController::class, 'get');

    return $app->run(Request::capture($request ?? null, $config));
```

### Asynchronous HTTP Server _(powered by [workerman](https://github.com/walkor/workerman))_

High perfomant Asychronous HTTP Server with support for serveral event-looping providers such as: `swoole`, `libevent`, `ev`, `libuv`, `react`. Provides the following features:

- Background workers to handle asynchronous task and cron job processing
- Concurrency with Promises and built in (`async`, `await`, `concurrent`) functions
- Real-time events with built in (`listen`, `dispatch`) functions
- Asynchronous queuing with built in (`enqueue`) function

```php
    # ./start.php

    $config = (new Config())
            ->setAppPath(dirname(__DIR__))
            ->setConfigPath('Configs')
            ->setViewPath('Views');
    $app = new App($config);

    $app->get('/product/{id}')->to(ProductController::class, 'get');

    $app->start("localhost", 8080,
        (new WorkerConfig)
            ->setLooper(Looper::DEFAULT)
            ->addJob(new CallableTask(function () {
                log_debug("Testing EVERY_MINUTE Cron Job");
            }), Cron::EVERY_MINUTE));

```

Run command to start application

```sh
# Windows
php start.php

# Linux (Recommended)
php start.php start
```

## Configs

Configure application

```php
    $config = (new Config())
        ->setAppPath(__DIR__)
        ->setConfigPath('Configs')
        ->setViewPath('Views')
        ->setSecret("mysamplesecret123456")
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
    $app = new App($config);
    ...
```

### Using Config Files

Configs can be attached using separate configuration files.

#### Create Config File

Add config file to your config path. E.g `myapp/Configs/database.php`

```php
    # database.php

    // Use constant
    define("DB_NAME", "my-db-dev");
    define("DB_HOST", "localhost");

    // Use dynamic configs
    return [
        'db_name'=>'my-db-dev',
        'db_host'=>'localhost',
    ];
    // Access dynamic configs
    // Set
    app()->config->set('db_name', 'my-db-dev-2');
    // Get
    app()->config->get('db_name');
```

#### Add Config File

```php
    ....
    $config->addFile('database')
    $app = new App($config);
    ....
```

## Route

Add HTTP routes.

### Controller Route

```php
    ....
    $app = new App($config);
    $app->get('/user/{id}')->to(UserController::class, 'get');
    $app->get('/user/{id}')->to(UserController::class, 'get');
    $app->post('/user/{id}')->to(UserController::class, 'create');
    $app->put('/user/{id}')->to(UserController::class, 'update'),
    $app->delete('/user/{id}')->to(UserController::class, 'delete'),
    $app->run();
```

### Anonymous Route

```php
    ....
    $app = new App($config);
    $app->get('/user/{id}')->call(function (RequestInterface $request, string $id) {
        // Perform action ...
    });
    $app->run();
```

### View Route

```php
    ....
    $app = new App($config);
    $app->get('/user/{id}')->view(UserPage::class);
    $app->run();
```

### Custom Route Class

```php
    ....
    $app = new App($config);
    // Using Custom Route Class - Single
    $app->router->addRoute(MyRoute::get('/user/{id}')->to(UserController::class, 'get'));
    // Using Custom Route Class - List
    $app->router->addRoutes([
        MyRoute::get('/user/{id}')->to(UserController::class, 'get'),
        MyRoute::post('/user')->to(UserController::class, 'create'),
        MyRoute::put('/user/{id}')->to(UserController::class, 'update'),
        MyRoute::delete('/user/{id}')->to(UserController::class, 'delete'),
    ]);
    $app->run();
```

## Providers

Extend application features and configurations.

### Create Provider

```php
class CustomProvider implements ProviderInterface
{

    /**
     * @inheritDoc
     */
    public function process(App $app): void
    {
        // Perform custom action....
    }
}
```

### Attach Provider

```php
    ...
    $app = new App($config);
    $app->addProvider(new CustomProvider());
    ...
```

## Middleware

Intercept HTTP request and response. PSR Middleware supported.

### Create Middleware

```php
    class AuthenticateMiddleware implements MiddlewareInterface
    {
        public function process(RequestInterface|RouteInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            // Perform custom action....
            // Or forward to next request handler
            return $handler->handle($request);
        }
    }
```

### Attach Middleware

```php
    # Attach global middleware
    ....
    $app = new App($config);
    $app->addMiddleware(new AuthenticateMiddleware())
    ....

    # Attach middleware to specific route
    ....
    $app->put('/user/{id}')->to(UserController::class, 'update')->middlewares([
        new AuthenticateMiddleware()
    ]);
    $app->router->addRoute(
        Route::put('/user/{id}')->to(UserController::class, 'update')->middlewares([
            new AuthenticateMiddleware()
        ])
    );
    ....
```

## Bindings

Bind an interface to a particular class. Hence, the specified class object will be used when resolving dependencies.

### Add Binding

```php
    ....
    $app = new App($config);
    $app->addBinding(CacheInterface::class, RedisCache::class)
    ....
```

### Resolve Binding

```php
    // Manually
    $cache = app()->make(CacheInterface::class)

    // Automatically
    class UserController
    {
        public function __construct(private CacheInterface $cache)
        {
        }
    }
```

## Views

### Generic Component

Add view file to your view path. E.g `myapp/Views/login.php`

```php
    # In Controller (or anywhere you wish to load view)
    // Using app instance
    app()->loader->view('login', ['username' => $uname, 'password' => $pass]);
    // Using helpers
    view('login', ['username' => $uname, 'password' => $pass]);
```

### Dedicated View Model

Add view file(s) to your view path. E.g `myapp/Views/LoginPage.php`, `myapp/Views/components/login.php`

```php
    # In-line rendering
    class LoginPage extends View
    {
        public function __construct(protected LoginPageDto|BaseDto|array|null $data = null, protected $headers = array())
        {
        }

        public function render()
        {
            $header = new HeaderComponent;
            return <<<HTML
            <html>
                <body>
                    <div>{$header}</div>
                    <div>Username: {$this->get("username")}</div>
                </body>
            </html>
            HTML;
        }
    }

    # Component rendering
    class LoginPage extends View
    {
        public function __construct(protected LoginPageDto|BaseDto|array|null $data = null, protected $headers = array())
        {
        }

        public function render()
        {
            return $this->include('components/login', true);
        }
    }
```

## Database _(Armie ORM)_

A simple but elegant database ORM built on top of PHP Data Objects (PDO)

### Define Model

```php
class ProductModel extends Model
{
    /**F
     * @inheritDoc
     */
    public function getFields(): array
    {
        return [
            new Field('id', DataType::INT),
            new Field('name', DataType::STRING),
            new Field('type', DataType::STRING),
            new Field('qty', DataType::INT),
            new Field('categoryId', DataType::INT),
            new Field('createdAt', DataType::DATETIME),
            new Field('updatedAt', DataType::DATETIME),
            new Field('deletedAt', DataType::DATETIME)
        ];
    }
    /**
     * @inheritDoc
     */
    public function getRelations(): array
    {
        return [
            new OneToOne('category', $this, new Reference(CategoryTestModel::class, ['categoryId' => 'id']))
        ];
    }
    /**
     * @inheritDoc
     */
    public function getTableName(): string
    {
        return 'products';
    }
    /**
     * @inheritDoc
     */
    public function getKeyName(): ?string
    {
        return 'id';
    }
    /**
     * @inheritDoc
     */
    public function getCreatedDateName(): ?string
    {
        return 'createdAt';
    }
    /**
     * @inheritDoc
     */
    public function getUpdatedDateName(): ?string
    {
        return 'updatedAt';
    }
    /**
     * @inheritDoc
     */
    public function getSoftDeleteDateName(): ?string
    {
        return 'deletedAt';
    }
}
```

#### Save Model (Create / Update)

```php
$model = ProductModel::create(['name' => 'IPhone 14', 'qty' => 3, 'type' => 'Mobile Phone', 'categoryId' => 1]);
$model = ProductModel::update(1, ['name' => 'IPhone 14', 'qty' => 3, 'type' => 'Mobile Phone', 'categoryId' => 1]);
// Or
...
$product = new ProductModel;
$product->load(['name' => 'IPhone 14', 'qty' => 3, 'type' => 'Mobile Phone', 'categoryId' => 1]);
$product->save();
```

#### Find Item

```php
...
$model = ProductModel::findById(1);
// Or
$model = (new ProductModel)->find(1);
```

#### Get List

```php
...
$model = ProductModel::getAll();
// Or
$model = (new ProductModel)->all();
```

### Define Repository

```php
class ProductRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(new ProductModel);
    }
}
// Or - Use Generic Repository
$productRepo = new Repository(new ProductModel)

```

#### Get Paginated List

```php
...
$productRepo = new ProductRepository();
$result = $productRepo->paginate(1, 3);
```

## Tests

To execute the test suite, you'll need to install all development dependencies.

```bash
$ git clone https://github.com/busarm/armie
$ composer install
$ composer test
```

You can use PHP server built-in server to test:

```bash
$ php -S localhost:8181 -t tests/TestApp
```

## License

The Armie Framework is licensed under the MIT license. See [License File](LICENSE) for more information.
