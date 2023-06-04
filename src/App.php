<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Middlewares\StatelessCookieMiddleware;
use Throwable;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\ErrorTraceDto;
use Busarm\PhpMini\Dto\ResponseDto;
use Busarm\PhpMini\Enums\AppStatus;
use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\Verbose;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\HttpException;
use Busarm\PhpMini\Handlers\RequestHandler;
use Busarm\PhpMini\Handlers\WorkermanSessionHandler;
use Busarm\PhpMini\Interfaces\ContainerInterface;
use Busarm\PhpMini\Interfaces\HTTP\CrudControllerInterface;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
use Busarm\PhpMini\Interfaces\ReportingInterface;
use Busarm\PhpMini\Interfaces\HttpServerInterface;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\ProviderInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;
use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Interfaces\SingletonStatelessInterface;
use Busarm\PhpMini\Middlewares\PsrMiddleware;
use Busarm\PhpMini\Middlewares\StatelessSessionMiddleware;
use Busarm\PhpMini\Traits\Container;
use Psr\Log\LoggerInterface;
use Psr\Http\Server\MiddlewareInterface as ServerMiddlewareInterface;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Response as HttpResponse;
use Workerman\Protocols\Http\Session\FileSessionHandler;
use Workerman\Worker;

use function Busarm\PhpMini\Helpers\is_cli;
use function Busarm\PhpMini\Helpers\log_debug;

// TODO Event Manager Interface - Handle sync and async dispatch
// TODO Queue Manager Interface - Handle sync and async jobs
// TODO PSR Cache Interface
// TODO PSR Session Interface - replace SessionStoreInterface & SessionManager
// TODO Restructure folders to be self contained - class + it's interface

/**
 * Application Factory
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class App implements HttpServerInterface, ContainerInterface
{
    use Container;

    /** @var static App instance */
    private static $__instance = null;

    /** @var RouterInterface */
    public $router = null;

    /** @var LoggerInterface */
    public $logger = null;

    /** @var LoaderInterface */
    public $loader = null;

    /** @var ReportingInterface */
    public $reporter = null;

    /** @var DependencyResolverInterface */
    public $resolver = null;

    /**
     * @var ServiceDiscoveryInterface
     */
    public $serviceDiscovery = null;


    /** @var boolean App is running in CLI mode*/
    public $isCli;

    /** @var int|float App start time in milliseconds */
    public $startTimeMs;

    /** @var boolean 
     * If app is running in stateless mode. E.g Using Swoole or other Event loops. 
     * Do not use static variables for user specific data when runing on stateless mode.
     * 
     * Stateless mode does not support adding:
     * - Application-wide singletons
     * */
    public bool $stateless = false;

    /**
     * Application main worker - when using event loop (stateless) mode
     *
     * @var Worker|null
     */
    public Worker|null $worker = null;


    /** @var ProviderInterface[] */
    protected $providers = [];

    /** @var MiddlewareInterface[] */
    protected $middlewares = [];

    /** @var array */
    protected $bindings = [];

    /**
     * App current status
     *
     * @var \Busarm\PhpMini\Enums\AppStatus::*
     */
    protected int $status = AppStatus::STOPPED;

    /**
     * @param Config $config App configuration object
     * @param \Busarm\PhpMini\Enums\Env::* $env App environment
     */
    public function __construct(public Config $config, public string $env = Env::LOCAL)
    {
        $this->status = AppStatus::INITIALIZING;

        // Set app instance
        self::$__instance = &$this;
        $this->startTimeMs = defined('APP_START_TIME') ? APP_START_TIME : floor(microtime(true) * 1000);


        // Benchmark start time

        // Set cli state
        $this->isCli = is_cli();

        // Set error reporter
        $this->reporter = new Reporter;

        // Set router
        $this->router = new Router;

        // Set dependency resolver
        $this->resolver = new Resolver($this);

        // Set Loader
        $this->loader = new Loader($this->config);

        // Set logger
        $this->logger = new ConsoleLogger(new ConsoleOutput(match ($this->config->loggerVerborsity) {
            Verbose::QUIET => OutputInterface::VERBOSITY_QUIET,
            Verbose::NORMAL => OutputInterface::VERBOSITY_NORMAL,
            Verbose::VERBOSE => OutputInterface::VERBOSITY_VERBOSE,
            Verbose::VERY_VERBOSE => OutputInterface::VERBOSITY_VERY_VERBOSE,
            Verbose::DEBUG => OutputInterface::VERBOSITY_DEBUG,
            default => OutputInterface::VERBOSITY_NORMAL
        }, true));

        // Set up error reporting
        $this->setUpErrorHandlers();

        // Load custom configs
        $this->loadConfigs();

        // Load custom providers
        $this->loadProviders();
    }

    /**  
     * Get application instance
     * @return self 
     */
    public static function &getInstance(): self
    {
        return self::$__instance;
    }

    ############################
    # Setup and Run
    ############################

    /**
     * Load custom config file
     * 
     * @param string $config File path relative to app path
     * @return self
     */
    private function loadConfig(string $config)
    {
        $configs = $this->loader->config($config);
        // Load configs into app
        if ($configs && is_array($configs)) {
            foreach ($configs as $key => $value) {
                $this->config->set($key, $value);
            }
        }
        return $this;
    }

    /**
     * Load custom configs
     */
    private function loadConfigs()
    {
        if (!empty($this->config->files)) {
            foreach ($this->config->files as $config) {
                $this->loadConfig((string) $config);
            }
        }
    }

    /**
     * Load providers
     */
    private function loadProviders()
    {
        if (!empty($this->providers)) {
            foreach ($this->providers as $provider) {
                $provider->process($this);
            }
        }
    }

    /**
     * Set up error handlers
     */
    private function setUpErrorHandlers()
    {
        set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
            $this->reporter->reportError("Internal Server Error", $errstr, $errfile, $errline);
            $this->showMessage(500, sprintf("Error: %s", $errstr), $errno, $errline, $errfile);
        });
        set_exception_handler(function (Throwable $e) {
            if ($e instanceof HttpException) {
                $e->handler($this)->send($this->config->httpSendAndContinue);
            } else {
                $this->reporter->reportException($e);
                $trace = array_map(function ($instance) {
                    return (new ErrorTraceDto($instance));
                }, $e->getTrace());
                $this->showMessage(500, sprintf("%s: %s", get_class($e), $e->getMessage()), $e->getCode(), $e->getLine(), $e->getFile(), $trace);
            }
        });
    }

    /**
     * Run application
     *
     * @param RequestInterface|RouteInterface|null $request Custom request or route object
     * @return ResponseInterface
     */
    public function run(RequestInterface|RouteInterface|null $request = null): ResponseInterface
    {
        $this->status = AppStatus::RUNNNIG;
        $startTime = $this->stateless ? microtime(true) * 1000 : $this->startTimeMs;

        // Set request & response & router
        $request = $request ?? Request::fromGlobal($this->config);

        // Leave logs for tracing
        if ($this->config->logRequest && $request instanceof RequestInterface) {
            log_debug(sprintf("Request started: id = %s, time = %s", $request->correlationId(), $startTime));
            $this->reporter->reportInfo([
                'time' => $startTime,
                'correlationId' => $request->correlationId(),
                'ip' => $request->ip(),
                'url' => $request->currentUrl(),
                'method' => $request->method(),
                'query' => $request->query()->all(),
                'body' => $request->request()->all(),
                'headers' => $request->request()->all(),
            ]);
        }

        // Set shutdown hook
        register_shutdown_function(function () use ($request, $startTime) {
            if ($request) {
                // Leave logs for tracing
                if ($this->config->logRequest && $request instanceof RequestInterface) {
                    $endTime = microtime(true) * 1000;
                    log_debug(sprintf("Request completed: id = %s, time = %s, duration-ms = %s", $request->correlationId(), $endTime, round($endTime - $startTime, 2)));
                }
                // Clean up request
                $request = NULL;
            }
            $this->status = AppStatus::STOPPED;
        });

        // Process route request
        $response = $this->processMiddleware($request);

        // Leave logs for tracing
        if ($this->config->logRequest && $request instanceof RequestInterface) {
            $endTime = microtime(true) * 1000;
            log_debug(sprintf("Request completed: id = %s, time = %s, duration-ms = %s", $request->correlationId(), $endTime, round($endTime - $startTime, 2)));
        }

        // Clean up request
        $request = NULL;

        return $response;
    }


    /**
     * Start application with event loop
     *
     * @param string $host
     * @param integer $port
     * @param integer $workers
     * @return void
     */
    public function start(string $host, int $port = 80, $workers = 1)
    {
        // Set workerman log file
        Worker::$logFile = $this->config->tempPath . DIRECTORY_SEPARATOR . 'workerman.log';

        // TODO  Add workers for background jobs

        //------- Add Custom Middlewares -------//

        $this->addMiddleware(new StatelessCookieMiddleware($this->config));
        $this->addMiddleware(new StatelessSessionMiddleware($this->config, $this->config->sessionHandler ?? new WorkermanSessionHandler(
            new FileSessionHandler($this->config->getSessionConfigs()),
            $this->config->encryptionKey
        )));

        //---- Main HTTP worker -----//

        $this->worker = new Worker('http://' . $host . ':' . $port);
        $this->worker->name = $this->config->name . ' v' . $this->config->version;
        $this->worker->count = $workers;

        $this->worker->onMessage = function (TcpConnection $connection, \Workerman\Protocols\Http\Request $request) {
            try {
                $request = Request::fromWorkerman($request, $this->config);
                $response = $this->run($request)->prepare();
                $connection->send(new HttpResponse($response->getStatusCode(), $response->getHttpHeaders(), $response->getBody()));
            } catch (Throwable $e) {
                $this->reporter->reportException($e);
                $response = (new Response)->json(ResponseDto::fromError($e, $this->env, $this->config->version)->toArray(), 500)->prepare();
                $connection->send(new HttpResponse($response->getStatusCode(), $response->getHttpHeaders(), $response->getBody()));
            }
        };
        $this->worker->onWorkerStart = function () {
            $this->status = AppStatus::RUNNNIG;
            $this->startTimeMs = floor(microtime(true) * 1000);
        };
        $this->worker->onWorkerStop = function () {
            $this->status = AppStatus::STOPPED;
            log_debug(sprintf("Worker %s stopped", $this->worker->name));
        };
        $this->worker->onWorkerExit = function () {
            $this->status = AppStatus::STOPPED;
            log_debug(sprintf("Worker %s exited", $this->worker->name));
        };

        //----- Start event loop ------//

        $this->setStateless(true);
        Worker::runAll();
    }

    /**
     * Process middleware
     *
     * @param RequestInterface|RouteInterface $request
     * @return ResponseInterface
     */
    protected function processMiddleware(RequestInterface|RouteInterface $request): ResponseInterface
    {
        try {
            // Add default response handler
            $action = fn (RequestInterface|RouteInterface &$request): ResponseInterface => $request instanceof RequestInterface ?
                (new Response(version: $request->version(), format: $this->config->httpResponseFormat))->html(sprintf("Not found - %s %s", $request->method(), $request->path()), 404) : (new Response(format: $this->config->httpResponseFormat))->html("Resource not found", 404);

            foreach (array_reverse(array_merge($this->middlewares, $this->router->process($request))) as $middleware) {
                $action = fn (RequestInterface|RouteInterface &$request): ResponseInterface => $middleware->process($request, new RequestHandler($action));
            }
            return ($action)($request);
        } catch (HttpException $e) {
            return $e->handler($this);
        } catch (Throwable $e) {
            $this->reporter->reportException($e);
            return (new Response)->json(ResponseDto::fromError($e, $this->env, $this->config->version)->toArray(), 500);
        }
    }

    /**
     * Instantiate class with dependencies
     * 
     * @param class-string<T> $className
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @param RequestInterface|RouteInterface|null $request HTTP request/route instance
     * @return T
     * @template T Item type template
     */
    public function make(string $className, array $params = [], RequestInterface|RouteInterface|null $request = null)
    {
        // Instantiate class
        $instance = (new DI($this))->instantiate(
            $className,
            $request,
            $params
        );

        // Add instance as singleton if supported
        if ($instance) {
            if ($request && $instance instanceof SingletonStatelessInterface) {
                $request->addSingleton($className, $instance);
            } else if ($instance instanceof SingletonInterface) {
                $this->addSingleton($className, $instance);
            }
        }
        return $instance;
    }

    /**
     * Add singleton
     * 
     * @inheritDoc
     */
    public function addSingleton(string $className, &$object): static
    {
        if ($this->status === AppStatus::RUNNNIG && $this->stateless) return $this;
        $this->singletons[$className] = $object;
        return $this;
    }

    /**
     * Add interface binding. Binds interface to a specific class which implements it
     *
     * @param string $interfaceName
     * @param string $className
     * @return self
     */
    public function addBinding(string $interfaceName, string $className)
    {
        if (!in_array($interfaceName, class_implements($className))) {
            throw new SystemError("`$className` does not implement `$interfaceName`");
        }
        $this->bindings[$interfaceName] = $className;
        return $this;
    }

    /**
     * Get interface binding
     *
     * @param string $interfaceName
     * @param string $default
     * @return string|null
     */
    public function getBinding(string $interfaceName, string $default = null): string|null
    {
        return $this->bindings[$interfaceName] ?? $default;
    }

    /**
     * Add provider
     *
     * @param ProviderInterface $provider
     * @return self
     */
    public function addProvider(ProviderInterface $provider)
    {
        $this->providers[] = $provider;
        return $this;
    }

    /**
     * Add middleware
     *
     * @param MiddlewareInterface|ServerMiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface|ServerMiddlewareInterface $middleware)
    {
        if ($middleware instanceof ServerMiddlewareInterface) {
            $this->middlewares[] = new PsrMiddleware($middleware, $this->config);
        } else {
            $this->middlewares[] = $middleware;
        }
        return $this;
    }

    /**
     * Set if app is running in stateless mode. E.g Using Swoole or other Event loops.
     *
     * @return  self
     */
    public function setStateless($stateless)
    {
        $this->stateless = $stateless;

        return $this;
    }

    /**
     * Set Logger
     *
     * @param LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Set router
     *
     * @param RouterInterface $router
     * @return self
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * Set error reporter
     * 
     * @param ReportingInterface $reporter
     * @return self
     */
    public function setReporter(ReportingInterface $reporter)
    {
        $this->reporter = $reporter;
        return $this;
    }

    /**
     * Set dependency resolver
     * 
     * @param DependencyResolverInterface $resolver
     * @return self
     */
    public function setDependencyResolver(DependencyResolverInterface $resolver)
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * Set service discovery
     * 
     * @param ServiceDiscoveryInterface $serviceDiscovery Service Discovery
     * @return self
     */
    public function setServiceDiscovery(ServiceDiscoveryInterface $serviceDiscovery): self
    {
        $this->serviceDiscovery = $serviceDiscovery;
        return $this;
    }

    ############################
    # HTTP Server Endpoints
    ############################

    /**
     * Set HTTP GET routes
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     *
     * @return RouteInterface
     */
    public function get(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::GET, $path);
    }

    /**
     * Set HTTP POST routes
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     *
     * @return RouteInterface
     */
    public function post(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::POST, $path);
    }

    /**
     * Set HTTP PUT routes
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     *
     * @return RouteInterface
     */
    public function put(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::PUT, $path);
    }

    /**
     * Set HTTP PATCH routes
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     *
     * @return RouteInterface
     */
    public function patch(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::PATCH, $path);
    }

    /**
     * Set HTTP DELETE routes
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     *
     * @return RouteInterface
     */
    public function delete(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::DELETE, $path);
    }

    /**
     * Set HTTP HEAD routes
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     *
     * @return RouteInterface
     */
    public function head(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::HEAD, $path);
    }

    /**
     * Set HTTP CRUD (CREATE/READ/UPDATE/DELETE) routes for controller
     * Creates the following routes:
     * - GET    $path/list
     * - GET    $path/paginate
     * - GET    $path/{id}
     * - POST   $path/bulk
     * - POST   $path
     * - PUT    $path/bulk
     * - PUT    $path/{id}
     * - DELETE $path/bulk
     * - DELETE $path/{id}
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @param class-string<CrudControllerInterface> $controller Application Controller class name e.g Home
     * @return mixed
     */
    public function crud(string $path, string $controller)
    {
        if (!in_array(CrudControllerInterface::class, class_implements($controller))) {
            throw new SystemError("`$controller` does not implement " . CrudControllerInterface::class);
        }

        $this->router->createRoute(HttpMethod::GET, "$path/list")->to($controller, 'list');
        $this->router->createRoute(HttpMethod::GET, "$path/paginate")->to($controller, 'paginatedList');
        $this->router->createRoute(HttpMethod::GET, "$path/{id}")->to($controller, 'get');
        $this->router->createRoute(HttpMethod::POST, "$path/bulk")->to($controller, 'createBulk');
        $this->router->createRoute(HttpMethod::POST, $path)->to($controller, 'create');
        $this->router->createRoute(HttpMethod::PUT, "$path/bulk")->to($controller, 'updateBulk');
        $this->router->createRoute(HttpMethod::PUT, "$path/{id}")->to($controller, 'update');
        $this->router->createRoute(HttpMethod::DELETE, "$path/bulk")->to($controller, 'deleteBulk');
        $this->router->createRoute(HttpMethod::DELETE, "$path/{id}")->to($controller, 'delete');
    }


    ############################
    # Response
    ############################


    /**
     * Show Message
     * @param int $status Status Code
     * @param string $message Message
     * @param string $errorCode 
     * @param int $errorLine 
     * @param string $errorFile 
     * @param array $errorTrace 
     * @return void
     */
    public function showMessage(
        $status,
        string|null $message = null,
        string|null $errorCode = null,
        int|null $errorLine = null,
        string|null $errorFile = null,
        array $errorTrace = []
    ) {
        if ($this->isCli) {
            if ($status !== 200 || $status !== 201) {
                $this->logger->error(
                    PHP_EOL . "message\t-\t$message" .
                        PHP_EOL . "code\t-\t$errorCode" .
                        PHP_EOL . "version\t-\t" . $this->config->version .
                        PHP_EOL . "path\t-\t$errorFile:$errorLine" .
                        PHP_EOL,
                    $errorTrace
                );
            } else {
                $this->logger->info(
                    PHP_EOL . "message\t-\t$message" .
                        PHP_EOL . "version\t-\t" . $this->config->version .
                        PHP_EOL
                );
            }
        } else {
            $response = new ResponseDto();
            $response->success = $status == 200 || $status == 201;
            $response->message = $message;
            $response->env = $this->env;
            $response->version = $this->config->version;

            // Show more info if not production
            if (!$response->success && $this->env !== Env::PROD) {
                $response->errorCode = !empty($errorCode) ? $errorCode : null;
                $response->errorLine = !empty($errorLine) ? $errorLine : null;
                $response->errorFile = !empty($errorFile) ? $errorFile : null;
                $response->errorTrace = !empty($errorTrace) ? json_decode(json_encode($errorTrace), 1) : null;
            }

            (new Response)->json($response->toArray(), ($status >= 100 && $status < 600) ? $status : 500)->send($this->config->httpSendAndContinue);
        }
    }

    /**
     * Send HTTP JSON Response
     * @param int $status Status Code
     * @param CollectionBaseDto|BaseDto|array|object|string|null $data Data
     * @param array $headers Headers
     * @return void
     */
    public function sendHttpResponse($status, $data = null, $headers = [])
    {
        if (!is_array($data)) {
            if ($data instanceof CollectionBaseDto) {
                $data = $data->toArray();
            } else if ($data instanceof BaseDto) {
                $data = $data->toArray();
            } else if (is_object($data)) {
                $data = (array) $data;
            } else {
                $response = new ResponseDto();
                $response->success = $status < 300;
                $response->message = $data;
                $data = $response->toArray();
            }
        }

        (new Response)
            ->addHttpHeaders($headers)
            ->json($data, ($status >= 100 && $status < 600) ? $status : 500)
            ->send($this->config->httpSendAndContinue);
    }
}
