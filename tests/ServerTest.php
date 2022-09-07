<?php

namespace Busarm\PhpMini\Test;

use PHPUnit\Framework\TestCase;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Server;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use GuzzleHttp\Client;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class ServerTest extends TestCase
{
    const HTTP_TEST_URL = 'http://localhost';
    const HTTP_TEST_PORT = 8181;

    private Server|null $server = NULL;
    private App|null $app = NULL;
    private Client $http;

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
        $this->server = new Server();
        $config = (new Config())
            ->setAppPath(__DIR__ . '/TestApp')
            ->setConfigPath('Configs')
            ->setViewPath('Views');
        $this->app = new App($config);
        $this->http = new Client(['timeout' => 10, 'base_uri' => self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT]);
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
        $this->assertNotNull($this->app->request);
        $this->assertNotNull($this->app->response);
        $this->assertInstanceOf(RequestInterface::class, $this->app->request);
        $this->assertInstanceOf(ResponseInterface::class, $this->app->response);
    }

    /**
     * Test server run HTTP for route
     *
     * @return void
     */
    public function testServerRunHTTPForRoute()
    {
        $response = $this->http->get('v1/pingHtml');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getBody());
    }

    /**
     * Test server run HTTP for domain
     *
     * @return void
     */
    public function testServerRunHTTPForDomain()
    {
        $response = $this->http->get('pingHtml');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getBody());
    }
}
