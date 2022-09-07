# PHP Mini by Busarm

A micro php framework designed for micro-services

## Installation

`composer require busarm/php-mini`

## Structure

App folders can be structured in whatever pattern you wish.

## Usage

### Single App

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

You can use PHP server built-in server to test:

```bash
$ php -S localhost:8181 -t tests/TestServer
```

### Multi Tenancy

```php
    require __DIR__ . '/../vendor/autoload.php';

    $server = (new Server())
        // Use `myapp` for requests with path `v1/....`
        ->addRoutePath('v1', __DIR__ . '/../myapp')
        // Use `mydevapp` for requests with domain name `dev.myapp.com`
        ->addDomainPath('dev.myapp.com', __DIR__ . '/../mydevapp');
    $server->run();
```

## Configs

Add config file to your config path. E.g `myapp/Configs/database.php`

```php
    # index.php (initialization script)
    ....
    $app = new App($config);
    $app->loadConfig('database')
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
    $app->loadConfig('route')
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
        ?>
            <html> </html>
        <?php
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

## Tests

To execute the test suite, you'll need to install all development dependencies.

```bash
$ git clone https://github.com/Busarm/php-mini
$ composer install
$ composer test
```

## License

The PHP Mini Framework is licensed under the MIT license. See [License File](LICENSE) for more information.
