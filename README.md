# PHP Mini by Busarm

A micro php framework designed for micro-services

## Installation

`composer require busarm/php-mini`

## Structure

App folders can be structured in whatever way pattern you wish

## Usage

```php
    define('APP_START_TIME', floor(microtime(true) * 1000));
    require __DIR__ . '/../vendor/autoload.php';

    $config = (new Config())
            ->setBasePath(dirname(__FILE__))
            ->setAppPath('myapp')
            ->setConfigPath('Configs')
            ->setViewPath('Views');
    $app = new App($config);
    $app->run();
```

## Configs

Add config file to your config path. E.g `myapp/Configs/database.php`

```php
    # index.php (initialization script)
    ....
    $app = new App($config);
    $app->loadConfig('database.php')
    ....

    # database.php
    // Use constant
    define("DB_NAME", "my-db-dev");
    define("DB_HOST", "localhost");
    // Use dynamic variable
    return [
        'db_name'=>'my-db-dev',
        'db_host'=>'localhost',
    ];

    # Access dynamic config in app
    // Get
    app()->config('db_name');
    // Set
    app()->config('db_name', 'my-db-dev-2');
```

## Route

### During Initialization

```php
    ....
    $app = new App($config);
    // Single
    $app->router->addRoute(Route::get('/user/{id}')->to(UserController::class, 'get'));
    // List
    $app->router->addRoutes([
        Route::get('/user/{id}')->to(UserController::class, 'get'),
        Route::post('/user')->to(UserController::class, 'create'),
        Route::put('/user/{id}')->to(UserController::class, 'update'),
        Route::delete('/user/{id}')->to(UserController::class, 'delete'),
    ]);
    $app->run();
```

### In Configs

Add route file to your config path. E.g `myapp/Configs/route.php`

```php
    # index.php (initialization script)
    ....
    $app = new App($config);
    $app->loadConfig('route.php')
    ....

    # route.php
    // Single
    app()->router->addRoute(Route::get('/user/{id}')->to(UserController::class, 'get'));
    // List
    app()->router->addRoutes([
        Route::get('/user/{id}')->to(UserController::class, 'get'),
        Route::post('/user')->to(UserController::class, 'create'),
        Route::put('/user/{id}')->to(UserController::class, 'update'),
        Route::delete('/user/{id}')->to(UserController::class, 'delete'),
    ]);
```

## Middleware

```php
    # AuthenticateMiddleware.php
    class AuthenticateMiddleware implements MiddlewareInterface
    {
        public function handle(App $app, Callable $next = null): mixed
        {
            // Perform action
            return $next ? $next() : true;
        }
    }

    # Attach global middleware
    ....
    $app = new App($config);
    $app->addMiddleware(new AuthenticateMiddleware())
    ....

    # Attach middleware to specific route
    ....
    $app->router->addRoute(
        Route::put('/user/{id}')->to(UserController::class, 'update')->middlewares([
            new AuthenticateMiddleware()
        ])
    );
    ....
```

## Bindings

Bind an interface to a particular class. Hence, the specified class object will be used when resolving dependencies.

```php
    # Add bindings
    ....
    $app = new App($config);
    $app->addBinding(CacheInterface::class, RedisCache::class)
    ....

    # Resolve binding
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

## Tests

To execute the test suite, you'll need to install all development dependencies.

```bash
$ git clone https://github.com/Busarm/php-mini
$ composer install
$ composer test
```

## License

The PHP Mini Framework is licensed under the MIT license. See [License File](LICENSE) for more information.
