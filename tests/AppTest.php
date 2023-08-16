<?php

namespace Armie\Test;

use Armie\Test\TestApp\Services\MockService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Armie\App;
use Armie\Config;
use Armie\Enums\HttpMethod;
use Armie\Interfaces\LoaderInterface;
use Armie\Interfaces\RouterInterface;
use Armie\Middlewares\CorsMiddleware;
use Armie\Request;
use Armie\Route;
use Armie\Test\TestApp\Controllers\HomeTestController;
use Armie\Bags\Bag;
use Armie\Configs\PDOConfig;
use Armie\Interfaces\RequestInterface;
use Armie\Test\TestApp\Controllers\AuthTestController;
use Armie\Test\TestApp\Controllers\ProductTestController;
use Armie\Test\TestApp\Services\MockStatelessService;
use Armie\Test\TestApp\Views\TestViewPage;
use Middlewares\Firewall;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @covers \Armie\App
 */
final class AppTest extends TestCase
{
    const HTTP_TEST_URL = 'http://localhost';
    const HTTP_TEST_PORT = 8181;

    private App|null $app = NULL;

    public static function setUpBeforeClass(): void
    {
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'armie'));
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
     * Test app setUp 
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
     * @covers \Armie\Test\TestApp\Controllers\HomeTestController
     * @return void
     */
    public function testAppRunCLI()
    {
        $response = $this->app->run(Route::init()->to(HomeTestController::class, 'ping'));
        $this->assertNotNull($response);
        $this->assertEquals('success-' . $this->app->env->value, strval($response->getBody()));
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
        $this->assertEquals('success-' . $this->app->env->value, $response->getBody());
    }

    /**
     * Test app run mock HTTP with view page as destination
     *
     * @return void
     */
    public function testAppRunMockHttpView()
    {
        $this->app->get('pingHtml/{name}')->view(TestViewPage::class);
        $response = $this->app->run(Request::fromUrl(self::HTTP_TEST_URL . ':' . self::HTTP_TEST_PORT . '/pingHtml/sam', HttpMethod::GET, $this->app->config));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString("Test View Component", strval($response->getBody()));
    }

    /**
     * Test app run mock HTTP CORS
     * 
     * @covers \Armie\Middlewares\CorsMiddleware
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
     * @covers \Armie\Test\TestApp\Services\MockService
     * @covers \Armie\Interfaces\SingletonInterface
     * @covers \Armie\Traits\Singleton
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
     * @covers \Armie\Test\TestApp\Services\MockService
     * @covers \Armie\Interfaces\SingletonInterface
     * @covers \Armie\Traits\Singleton
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
     * @covers \Armie\Test\TestApp\Services\MockStatelessService
     * @covers \Armie\Interfaces\SingletonStatelessInterface
     * @covers \Armie\Traits\SingletonStateless
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
     * Test app run mock HTTP Resource Controller
     *
     * @group skip
     * @return void
     */
    public function testAppRunMockHttpResourceController()
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

        $this->app->router->addResourceRoutes('product', ProductTestController::class);
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
     * @covers \Armie\Interfaces\Attributes
     * @covers \Armie\Interfaces\Attributes
     * @covers \Armie\Test\TestApp\Attributes
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
     * @covers \Armie\Interfaces\Attributes
     * @covers \Armie\Interfaces\Attributes
     * @covers \Armie\Test\TestApp\Attributes
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
