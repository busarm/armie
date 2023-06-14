<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Middlewares\StatelessCookieMiddleware;
use Throwable;

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
use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Server\MiddlewareInterface as ServerMiddlewareInterface;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as HttpRequest;
use Workerman\Protocols\Http\Session\FileSessionHandler;
use Workerman\Worker;

use function Busarm\PhpMini\Helpers\is_cli;

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

    /**
     * List of classes that must be handled stateless when running in stateless mode
     */
    public static $statelessClasses = [
        Request::class,
        HttpRequest::class,
        RequestInterface::class,
        MessageRequestInterface::class,
        ServerRequestInterface::class,
        Response::class,
        ResponseInterface::class,
        MessageResponseInterface::class,
        Route::class,
        RouteInterface::class
    ];

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
     */
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
     * [RESTRICTED]
     *
     * @param mixed $key
     * @param mixed $val
     */
    public function __set($key, $val)
    {
        throw new SystemError("This action has been forbidden");
    }

    /**
     * [RESTRICTED]
     *
     * @param mixed $key
     */
    public function __get($key)
    {
        throw new SystemError("This action has been forbidden");
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
                $e->handler($this)->send($this->config->http->sendAndContinue);
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
     * Process application request
     *
     * @param RequestInterface|RouteInterface|null $request Custom request or route object
     * @return ResponseInterface
     */
    public function run(RequestInterface|RouteInterface|null $request = null): ResponseInterface
    {
        $this->status = AppStatus::RUNNNIG;
        $startTime = $this->stateless ? microtime(true) * 1000 : $this->startTimeMs;

        // Set request
        $request = $request ?? Request::fromGlobal($this->config);

        // Leave logs for tracing
        if ($this->config->logRequest && $request instanceof RequestInterface) {
            $this->logger->debug(sprintf("Request started: id = %s, time = %s", $request->correlationId(), $startTime));
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
                if ($request instanceof RequestInterface) {

                    // Leave logs for tracing
                    if ($this->config->logRequest && $request instanceof RequestInterface) {
                        $endTime = microtime(true) * 1000;
                        $this->logger->debug(sprintf("Request completed: id = %s, time = %s, duration-ms = %s", $request->correlationId(), $endTime, round($endTime - $startTime, 2)));
                    }

                    // Save session
                    $request->session()?->save();
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
            $this->logger->debug(sprintf("Request completed: id = %s, time = %s, duration-ms = %s", $request->correlationId(), $endTime, round($endTime - $startTime, 2)));
        }

        // Clean up request
        $request = NULL;

        return $response;
    }


    /**
     * Start async http server
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
        Worker::$statusFile = $this->config->appPath . DIRECTORY_SEPARATOR . 'workerman.status';
        Worker::$pidFile = $this->config->appPath . DIRECTORY_SEPARATOR . 'workerman.pid';

        // TODO  Add workers for background jobs

        //------- Add Custom Middlewares -------//

        $this->addMiddleware(new StatelessCookieMiddleware($this->config));
        $this->addMiddleware(new StatelessSessionMiddleware($this->config, $this->config->sessionHandler ?? new WorkermanSessionHandler(
            new FileSessionHandler($this->config->getSessionConfigs()),
            $this->config->encryptionKey
        )));

        //---- Main HTTP worker -----//

        // Set up SSL context.
        $ssl = $this->config->sslEnabled && $this->config->sslCertPath && $this->config->sslPkPath;
        $context = $ssl ? [
            'ssl' => [
                'local_cert'  => $this->config->sslCertPath,
                'local_pk'    => $this->config->sslPkPath,
                'verify_peer' => $this->config->sslVerifyPeer,
            ]
        ] : [];

        // Init Worker
        $this->worker = new Worker($ssl ? 'https://' : 'http://' . $host . ':' . $port, $context);
        $this->worker->name = $this->config->name . ' v' . $this->config->version;
        $this->worker->count = $workers;
        if ($ssl) $this->worker->transport = 'ssl';

        // Add handlers
        $this->worker->onMessage = function (TcpConnection $connection, HttpRequest $request) {
            try {
                $request = Request::fromWorkerman($request, $this->config);
                $response = $this->run($request)->prepare();
                $connection->send($response->toWorkerman());
            } catch (Throwable $e) {
                $this->reporter->reportException($e);
                $response = (new Response)->json(ResponseDto::fromError($e, $this->env, $this->config->version)->toArray(), 500)->prepare();
                $connection->send($response->toWorkerman());
            }
        };
        $this->worker->onWorkerStart = function (Worker $worker) {
            $this->status = AppStatus::RUNNNIG;
            $this->startTimeMs = floor(microtime(true) * 1000);
            $this->logger->debug(sprintf("Worker %s process %s started", $worker->name, $worker->id));
        };
        $this->worker->onWorkerStop = function (Worker $worker) {
            $this->status = AppStatus::STOPPED;
            $this->logger->debug(sprintf("Worker %s process %s stopped", $worker->name, $worker->id));
        };
        $this->worker->onWorkerExit = function (Worker $worker, $master, $pid) {
            $this->status = AppStatus::STOPPED;
            $this->logger->debug(sprintf("Worker %s master %s pid %s exited", $worker->name, $master, $pid));
        };
        $this->worker->onConnect = function (ConnectionInterface $connection) {
            $this->config->logRequest
                &&  $this->logger->debug(sprintf("Connection from %s started; through %s", $connection->getRemoteIp(), $connection->getRemotePort()));
        };
        $this->worker->onClose = function (ConnectionInterface $connection) {
            $this->config->logRequest
                &&  $this->logger->debug(sprintf("Connection from %s closed; sent to %s", $connection->getRemoteIp(), $connection->getRemotePort()));
        };
        $this->worker->onError = function ($error) {
            $this->logger->error(strval($error));
        };

        // TODO Add socket handler, socket request dto and socket data dto

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
                (new Response(version: $request->version(), format: $this->config->http->responseFormat))->html(sprintf("Not found - %s %s", $request->method(), $request->path()), 404) : (new Response(format: $this->config->http->responseFormat))->html("Resource not found", 404);

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
        // Prevent setting global singletons for stateless classes
        // with stateless mode if app is running
        if (
            $this->status === AppStatus::RUNNNIG
            && $this->stateless
            && in_array($className, self::$statelessClasses)
        ) return $this;

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
     * @inheritDoc
     */
    public function get(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::GET, $path);
    }

    /**
     * @inheritDoc
     */
    public function post(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::POST, $path);
    }

    /**
     * @inheritDoc
     */
    public function put(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::PUT, $path);
    }

    /**
     * @inheritDoc
     */
    public function patch(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::PATCH, $path);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::DELETE, $path);
    }

    /**
     * @inheritDoc
     */
    public function head(string $path): RouteInterface
    {
        return $this->router->createRoute(HttpMethod::HEAD, $path);
    }

    /**
     * @inheritDoc
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

            (new Response)->json($response->toArray(), ($status >= 100 && $status < 600) ? $status : 500)->send($this->config->http->sendAndContinue);
        }
    }
}
