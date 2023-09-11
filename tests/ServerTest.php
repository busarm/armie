<?php

namespace Armie\Test;

use Armie\App;
use Armie\Config;
use Armie\Enums\HttpMethod;
use Armie\Interfaces\LoaderInterface;
use Armie\Interfaces\RouterInterface;
use Armie\Server;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @covers \Armie\Server
 */
final class ServerTest extends TestCase
{
    const HTTP_TEST_URL = 'http://localhost';
    const HTTP_TEST_PORT = 8181;

    private Server|null $server = null;
    private App|null $app = null;

    public static function setUpBeforeClass(): void
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'armie'));
        defined('APP_START_TIME') or define('APP_START_TIME', floor(microtime(true) * 1000));
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->server = new Server('Test Server');
        $config = (new Config())
            ->setAppPath(__DIR__.'/app/v1')
            ->setConfigPath('Configs')
            ->setViewPath('Views')
            ->setLogRequest(false);
        $this->app = new App($config);
    }

    /**
     * Test app setUp.
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
     * Test app run mock HTTP For Route.
     *
     * @return void
     */
    public function testServerRunHttpForRoute()
    {
        $this->server->addRoutePath('v1', __DIR__.'/app/v1');
        $response = $this->server->run(new ServerRequest(HttpMethod::GET->value, self::HTTP_TEST_URL.':'.self::HTTP_TEST_PORT.'/v1/ping'));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test app run mock HTTP For Domain.
     *
     * @return void
     */
    public function testServerRunHttpForDomain()
    {
        $this->server->addDomainPath('localhost:'.ServerTest::HTTP_TEST_PORT, __DIR__.'/app/v1');
        $response = $this->server->run(new ServerRequest(HttpMethod::GET->value, self::HTTP_TEST_URL.':'.self::HTTP_TEST_PORT.'/ping'));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
