<?php

namespace Busarm\PhpMini\Test;

use PHPUnit\Framework\TestCase;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Route;
use Busarm\PhpMini\Router;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @covers \Busarm\PhpMini\App
 */
final class AppTest extends TestCase
{
    const HTTP_TEST_URL = 'http://localhost';
    const HTTP_TEST_PORT = 8181;

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
            ->setAppPath(__DIR__ . '/TestApp')
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
     * Test app run CLI
     *
     * @covers \Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController
     * @return void
     */
    public function testAppRunCLI()
    {
        $this->app->setRouter(Router::withController(HomeTestController::class, 'ping'));
        $response = $this->app->run();
        $this->assertNotNull($response);
        $this->assertEquals('success', $response->getBody());
    }

    /**
     * Test app run mock HTTP
     *
     * @return void
     */
    public function testAppRunMockHttp()
    {
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml'));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getBody());
    }

    /**
     * Test app run mock HTTP CORS
     * 
     * @covers \Busarm\PhpMini\Middlewares\CorsMiddleware
     * @return void
     */
    public function testAppRunMockHttpCORS()
    {
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml', HttpMethod::OPTIONS));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Preflight Ok', $response->getBody());
    }

    /**
     * Test app run mock HTTP CORS Active
     *
     * @return void
     */
    public function testAppRunMockHttpCORSActive()
    {
        $this->app->config->setHttpCheckCors(true);
        $this->app->config->setHttpAllowAnyCorsDomain(true);
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml', HttpMethod::OPTIONS));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Preflight Ok', $response->getBody());
        $this->assertEquals('*', $response->getHttpHeader('Access-Control-Allow-Origin'));
    }

    /**
     * Test app run mock HTTP CORS Active with custom origin
     *
     * @return void
     */
    public function testAppRunMockHttpCORSActiveOrigin()
    {
        $this->app->config->setHttpCheckCors(true);
        $this->app->config->setHttpAllowAnyCorsDomain(false);
        $this->app->config->setHttpAllowedCorsOrigins([
            'localhost:81'
        ]);
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(Request::fromUrl(
            self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml',
            HttpMethod::OPTIONS,
            [
                'HTTP_ORIGIN' => 'localhost:81'
            ]
        ));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Preflight Ok', $response->getBody());
        $this->assertEquals('localhost:81', $response->getHttpHeader('Access-Control-Allow-Origin'));
    }

    /**
     * Test app run mock HTTP CORS Inactive
     *
     * @covers \Busarm\PhpMini\Middlewares\CorsMiddleware
     * @return void
     */
    public function testAppRunMockHttpCORSInactive()
    {
        $this->app->config->setHttpCheckCors(false);
        $this->app->config->setHttpAllowAnyCorsDomain(true);
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml', HttpMethod::OPTIONS));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Preflight Ok', $response->getBody());
        $this->assertEmpty($response->getHttpHeader('Access-Control-Allow-Origin'));
    }
}
