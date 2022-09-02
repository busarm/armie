<?php

namespace Busarm\PhpMini\Test\TestApp;

use PHPUnit\Framework\TestCase;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Router;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class AppTest extends TestCase
{

    private App|null $app = NULL;

    public static function setupBeforeClass(): void
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'php-mini'));
        defined('APP_START_TIME') or define('APP_START_TIME', floor(microtime(true) * 1000));
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $config = (new Config())
            ->setBasePath(dirname(__FILE__))
            ->setAppPath('TestApp')
            ->setConfigPath('Configs')
            ->setViewPath('Views');
        $this->app = new App($config);
    }

    /**
     * Test app setup 
     *
     * @return void
     */
    public function testInitializeApp()
    {
        $this->assertNotNull($this->app);
        $this->assertNotNull($this->app->request);
        $this->assertNotNull($this->app->response);
        $this->assertInstanceOf(RequestInterface::class, $this->app->request);
        $this->assertInstanceOf(ResponseInterface::class, $this->app->response);
    }

    /**
     * Test app run
     *
     * @return void
     */
    public function testAppRun()
    {
        $this->app->config->setHttpSendAndContinue(true);
        $this->app->setRouter(Router::withController(HomeTestController::class, 'ping'));
        $result = $this->app->run();
        $this->assertNotNull($result);
        $this->assertEquals('success', $result->getBody());
    }
}
