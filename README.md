# PHP Mini by Busarm

A micro php framework designed for micro-services

# Usage

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
