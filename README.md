# PHP Mini by Busarm

A micro php framework designed for micro-services

## Installation
`composer require busarm/php-mini`

## Usage

```php
    define('APP_START_TIME', floor(microtime(true) * 1000));
    require __DIR__ . '/../vendor/autoload.php';

    $config = (new Config())
            ->setBasePath(dirname(__FILE__))
            ->setAppPath('myapp')
            ->setConfigPath('Configs')
            ->setViewPath('Views');
    $this->app = new App($config);
    $app->run();
```