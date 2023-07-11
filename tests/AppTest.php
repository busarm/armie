<?php

namespace Busarm\PhpMini\Test;

use Busarm\PhpMini\Test\TestApp\Services\MockService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Middlewares\CorsMiddleware;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Route;
use Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController;
use Busarm\PhpMini\Bags\Bag;
use Busarm\PhpMini\Configs\PDOConfig;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Test\TestApp\Controllers\AuthTestController;
use Busarm\PhpMini\Test\TestApp\Controllers\ProductTestController;
use Busarm\PhpMini\Test\TestApp\Services\MockStatelessService;
use Busarm\PhpMini\Test\TestApp\Views\TestViewPage;
use Middlewares\Firewall;

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

    public static function tearDownAfterClass(): void
    {
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
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
        $this->assertNotNull($this->app);
        $this->assertNotNull($this->app->router);
        $this->assertNotNull($this->app->loader);
        $this->assertNotNull($this->app->logger);
        $this->assertInstanceOf(RouterInterface::class, $this->app->router);
        $this->assertInstanceOf(LoaderInterface::class, $this->app->loader);
        $this->assertInstanceOf(LoggerInterface::class, $this->app->logger);
    }

    /**
     * Test app run CLI
     *
     * @covers \Busarm\PhpMini\Test\TestApp\Controllers\HomeTestController
     * @return void
     */
    public function testAppRunCLI()
    {
        $response = $this->app->run(Route::init()->to(HomeTestController::class, 'ping'));
        $this->assertNotNull($response);
        $this->assertEquals('success-' . $this->app->env, strval($response->getBody()));
    }

    /**
     * Test app run mock HTTP
     *
     * @return void
     */
    public function testAppRunMockHttp()
    {
        $this->app->get('pingHtml')->to(HomeTestController::class, 'pingHtml');
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml', HttpMethod::GET, $this->app->config));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success-' . $this->app->env, $response->getBody());
    }

    /**
     * Test app run mock HTTP with view page as destination
     *
     * @return void
     */
    public function testAppRunMockHttpView()
    {
        $this->app->get('pingHtml')->view(TestViewPage::class);
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml', HttpMethod::GET, $this->app->config));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString("Test View Component", strval($response->getBody()));
    }

    /**
     * Test app run mock HTTP CORS
     * 
     * @covers \Busarm\PhpMini\Middlewares\CorsMiddleware
     * @return void
     */
    public function testAppRunMockHttpCORS()
    {
        $this->app->addMiddleware(new CorsMiddleware($this->app->config));
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(Request::fromUrl(
            self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml',
            HttpMethod::OPTIONS,
            $this->app->config
        ));
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
        $this->app->config->http->setCheckCors(true);
        $this->app->config->http->setAllowAnyCorsDomain(true);
        $this->app->addMiddleware(new CorsMiddleware($this->app->config));
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(
            Request::fromUrl(
                self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml',
                HttpMethod::OPTIONS,
                $this->app->config
            )->setServer((new Bag([
                'HTTP_ORIGIN' => 'localhost:81'
            ])))->initialize()
        );

        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Preflight Ok', $response->getBody());
        $this->assertEquals('localhost:81', $response->getHttpHeader('Access-Control-Allow-Origin'));
    }

    /**
     * Test app run mock HTTP CORS Active with custom origin
     *
     * @return void
     */
    public function testAppRunMockHttpCORSActiveOrigin()
    {
        $this->app->config->http->setCheckCors(true);
        $this->app->config->http->setAllowAnyCorsDomain(false);
        $this->app->config->http->setAllowedCorsOrigins([
            'localhost:81'
        ]);
        $this->app->addMiddleware(new CorsMiddleware($this->app->config));
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(
            Request::fromUrl(
                self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml',
                HttpMethod::OPTIONS,
                $this->app->config
            )->setServer((new Bag([
                'HTTP_ORIGIN' => 'localhost:81'
            ])))->initialize()
        );
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Preflight Ok', $response->getBody());
        $this->assertEquals('localhost:81', $response->getHttpHeader('Access-Control-Allow-Origin'));
    }

    /**
     * Test app run mock HTTP CORS Active with custom origin failed
     *
     * @return void
     */
    public function testAppRunMockHttpCORSActiveOriginFailed()
    {
        $this->app->config->http->setCheckCors(true);
        $this->app->config->http->setAllowAnyCorsDomain(false);
        $this->app->config->http->setAllowedCorsOrigins([
            'localhost:81'
        ]);
        $this->app->addMiddleware(new CorsMiddleware($this->app->config));
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(
            Request::fromUrl(
                self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml',
                HttpMethod::OPTIONS,
                $this->app->config
            )->setServer((new Bag([
                'HTTP_ORIGIN' => 'localhost:8080'
            ])))->initialize()
        );
        $this->assertNotNull($response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getBody());
    }

    /**
     * Test app run mock HTTP CORS Inactive
     *
     * @return void
     */
    public function testAppRunMockHttpCORSInactive()
    {
        $this->app->config->http->setCheckCors(false);
        $this->app->config->http->setAllowAnyCorsDomain(true);
        $this->app->addMiddleware(new CorsMiddleware($this->app->config));
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(Request::fromUrl(
            self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml',
            HttpMethod::OPTIONS,
            $this->app->config
        ));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Preflight Ok', $response->getBody());
        $this->assertEmpty($response->getHttpHeader('Access-Control-Allow-Origin'));
    }

    /**
     * Test app singletons
     *
     * @covers \Busarm\PhpMini\Test\TestApp\Services\MockService
     * @covers \Busarm\PhpMini\Interfaces\SingletonInterface
     * @covers \Busarm\PhpMini\Traits\Singleton
     * @return void
     */
    public function testAppSingleton()
    {
        $mockService = MockService::make(['id' => uniqid()]);
        $newMockService = MockService::make(['id' => uniqid()]);
        $this->assertNotNull($mockService);
        $this->assertNotNull($newMockService);
        $this->assertEquals($mockService->id, $newMockService->id);
    }

    /**
     * Test app singletons for async class on async mode - should not be supported
     *
     * @covers \Busarm\PhpMini\Test\TestApp\Services\MockService
     * @covers \Busarm\PhpMini\Interfaces\SingletonInterface
     * @covers \Busarm\PhpMini\Traits\Singleton
     * @return void
     */
    public function testAppSingletonNotSupportedOnStatelessRequest()
    {
        App::$statelessClasses[] = MockService::class;
        $this->app->async = true;
        $this->app->router->addRoutes([
            Route::get('ping')->call(function () {
                $mockService = MockService::make(['id' => uniqid()]);
                $newMockService = MockService::make(['id' => uniqid()]);
                $this->assertNotNull($mockService);
                $this->assertNotNull($newMockService);
                $this->assertNotEquals($mockService->id, $newMockService->id);
            })
        ]);
        $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/ping', HttpMethod::GET, $this->app->config));
    }

    /**
     * Test stateless singletons
     *
     *
     * @covers \Busarm\PhpMini\Test\TestApp\Services\MockStatelessService
     * @covers \Busarm\PhpMini\Interfaces\SingletonStatelessInterface
     * @covers \Busarm\PhpMini\Traits\SingletonStateless
     * @return void
     */
    public function testStatelessSingleton()
    {
        $this->app->async = true;
        $this->app->router->addRoutes([
            Route::get('ping')->call(function (App $app, RequestInterface $request) {
                $mockService = MockStatelessService::make($request, ['id' => uniqid()]);
                $newMockService = MockStatelessService::make($request, ['id' => uniqid()]);
                $this->assertNull($app->getSingleton(MockStatelessService::class));
                $this->assertNotNull($mockService);
                $this->assertNotNull($newMockService);
                $this->assertEquals($mockService->id, $newMockService->id);
            })
        ]);
        $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/ping', HttpMethod::GET, $this->app->config));
    }

    /**
     * Test app run mock HTTP PSR Middlleware (Firewall)
     *
     * @return void
     */
    public function testAppRunMockHttpPSRMiddleware()
    {
        $this->app->addMiddleware((new Firewall(['127.0.0.1'])));
        $this->app->router->addRoutes([
            Route::get('pingHtml')->to(HomeTestController::class, 'pingHtml')
        ]);
        $response = $this->app->run(
            Request::fromUrl(
                self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml',
                HttpMethod::GET,
                $this->app->config
            )->setServer((new Bag([
                'REMOTE_ADDR' => '127.0.0.2'
            ])))->initialize()
        );
        $this->assertNotNull($response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test app run mock HTTP CRUD Controller
     *
     * @group skip
     * @return void
     */
    public function testAppRunMockHttpCrudController()
    {
        $this->app->config
            ->setAppPath(__DIR__ . '/TestApp')
            ->setConfigPath('Configs')
            ->setViewPath('Views')
            ->setDb((new PDOConfig)
                ->setConnectionDriver("mysql")
                ->setConnectionHost("localhost")
                ->setConnectionDatabase('default')
                ->setConnectionPort(3306)
                ->setConnectionUsername("root")
                ->setConnectionPassword("root")
                ->setConnectionPersist(false)
                ->setConnectionErrorMode(true));

        $this->app->router->addCrudRoutes('product', ProductTestController::class);
        $response = $this->app->run(
            Request::fromUrl(
                self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/product/paginate?limit=2&page=10',
                HttpMethod::GET,
                $this->app->config
            )
        );
        $this->assertNotNull($response);
        $this->assertNotNull($response->getParameters());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test attribute auth Ok
     *
     * @covers \Busarm\PhpMini\Interfaces\Attributes
     * @covers \Busarm\PhpMini\Interfaces\Attributes
     * @covers \Busarm\PhpMini\Test\TestApp\Attributes
     * @return void
     */
    public function testAttributeAuthOk()
    {
        $this->app->get('auth/test')->to(AuthTestController::class, 'test');
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/auth/test', HttpMethod::GET, $this->app->config)
            ->setServer((new Bag([
                'HTTP_AUTHORIZATION' => 'php112233445566'
            ])))->initialize());
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('authorized', $response->getBody());
    }

    /**
     * Test attribute auth failed
     *
     * @covers \Busarm\PhpMini\Interfaces\Attributes
     * @covers \Busarm\PhpMini\Interfaces\Attributes
     * @covers \Busarm\PhpMini\Test\TestApp\Attributes
     * @return void
     */
    public function testAttributeAuthFailed()
    {
        $this->app->get('auth/test')->to(AuthTestController::class, 'test');
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/auth/test', HttpMethod::GET, $this->app->config));
        $this->assertNotNull($response);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
