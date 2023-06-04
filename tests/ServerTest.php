<?php

namespace Busarm\PhpMini\Test;

use PHPUnit\Framework\TestCase;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Busarm\PhpMini\Server;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Nyholm\Psr7\ServerRequest;
use Psr\Log\LoggerInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @covers \Busarm\PhpMini\Server
 */
final class ServerTest extends TestCase
{
    const HTTP_TEST_URL = 'http://localhost';
    const HTTP_TEST_PORT = 8181;

    private Server|null $server = NULL;
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
        $this->server = new Server("Test Server");
        $config = (new Config())
            ->setAppPath(__DIR__ . '/TestApp')
            ->setConfigPath('Configs')
            ->setViewPath('Views')
            ->setLogRequest(false);
        $this->app = new App($config);
    }

    /**
     * Test app setup 
     *
     * @return void
     */
    public function testInitializeApp()
    {
        $this->assertNotNull($this->server);
        $this->assertNotNull($this->app);
        $this->assertNotNull($this->app->router);
        $this->assertNotNull($this->app->loader);
        $this->assertNotNull($this->app->logger);
        $this->assertInstanceOf(RouterInterface::class, $this->app->router);
        $this->assertInstanceOf(LoaderInterface::class, $this->app->loader);
        $this->assertInstanceOf(LoggerInterface::class, $this->app->logger);
    }

    /**
     * Test app run mock HTTP For Route
     *
     * @return void
     */
    public function testServerRunHttpForRoute()
    {
        $this->server->addRoutePath('v1', __DIR__ . '/TestApp');
        $response = $this->server->run(new ServerRequest(HttpMethod::GET, self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/v1/ping'));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }


    /**
     * Test app run mock HTTP For Domain
     *
     * @return void
     */
    public function testServerRunHttpForDomain()
    {
        $this->server->addDomainPath('localhost:' . ServerTest::HTTP_TEST_PORT, __DIR__ . '/TestApp');
        $response = $this->server->run(new ServerRequest(HttpMethod::GET, self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/ping'));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
