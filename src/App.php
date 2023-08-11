<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Configs\WorkerConfig;
use Busarm\PhpMini\Middlewares\StatelessCookieMiddleware;
use Throwable;

use Busarm\PhpMini\Dto\ResponseDto;
use Busarm\PhpMini\Dto\TaskDto;
use Busarm\PhpMini\Enums\AppStatus;
use Busarm\PhpMini\Enums\Cron;
use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\Looper;
use Busarm\PhpMini\Enums\Verbose;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Events\LocalEventManager;
use Busarm\PhpMini\Exceptions\HttpException;
use Busarm\PhpMini\Handlers\RequestHandler;
use Busarm\PhpMini\Handlers\WorkermanSessionHandler;
use Busarm\PhpMini\Interfaces\ContainerInterface;
use Busarm\PhpMini\Interfaces\Data\ResourceControllerInterface;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
use Busarm\PhpMini\Interfaces\DistributedServiceDiscoveryInterface;
use Busarm\PhpMini\Interfaces\Event\EventManagerInterface;
use Busarm\PhpMini\Interfaces\HttpServerInterface;
use Busarm\PhpMini\Interfaces\ReportingInterface;
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
use Busarm\PhpMini\Tasks\Task;
use Busarm\PhpMini\Traits\Container;
use Exception;
use Laravel\SerializableClosure\SerializableClosure;
use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Server\MiddlewareInterface as ServerMiddlewareInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Events\Ev;
use Workerman\Events\Event;
use Workerman\Events\React\Base;
use Workerman\Events\Select;
use Workerman\Events\Swoole;
use Workerman\Events\Uv;
use Workerman\Protocols\Http\Request as HttpRequest;
use Workerman\Protocols\Http\Session\FileSessionHandler;
use Workerman\Timer;
use Workerman\Worker;

use function Busarm\PhpMini\Helpers\error_level;
use function Busarm\PhpMini\Helpers\is_cli;
use function Busarm\PhpMini\Helpers\log_warning;
use function Busarm\PhpMini\Helpers\serialize;

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
     * List of classes that must be handled as stateless when running in async mode
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
    private static ?self $__instance = null;


    /** @var RouterInterface */
    public ?RouterInterface $router = null;

    /** @var LoggerInterface */
    public ?LoggerInterface $logger = null;

    /** @var LoaderInterface */
    public ?LoaderInterface $loader = null;

    /** @var ReportingInterface */
    public ?ReportingInterface $reporter = null;

    /** @var DependencyResolverInterface */
    public ?DependencyResolverInterface $resolver = null;

    /**
     * @var ServiceDiscoveryInterface
     */
    public ?ServiceDiscoveryInterface $serviceDiscovery = null;

    /** @var DI */
    public $di = null;

    /**
     * @var EventManagerInterface
     */
    public ?EventManagerInterface $eventManager = null;


    /** @var boolean App is running in CLI mode*/
    public bool $isCli;

    /** @var int|float App start time in milliseconds */
    public int|float $startTimeMs;

    /** 
     * @var boolean 
     * If app is running in asynchronous mode and supports asynchronous requests. E.g Using event loops such as Ev, Swoole. 
     * ### NOTE: Take caution when using static variables. TAKE CAUTION when using static variables.
     */
    public bool $async = false;

    /**
     * Application main worker - when using event loop (async) mode
     *
     * @var Worker|null
     */
    public Worker|null $worker = null;

    /**
     * Application tasks worker - when using event loop (async) mode
     *
     * @var Worker|bool|null
     */
    public Worker|bool|null $taskWorker = null;


    /** @var ProviderInterface[] */
    protected $providers = [];

    /** @var MiddlewareInterface[] */
    protected $middlewares = [];

    /** @var array<string, string> */
    protected $bindings = [];


    /**
     * App current status
     *
     * @var AppStatus
     */
    protected AppStatus $status = AppStatus::STOPPED;

    /**
     * @param Config $config App configuration object
     * @param Env $env App environment
     */
    public function __construct(public Config $config, public Env $env = Env::LOCAL)
    {
        if (empty($this->config->appPath)) throw new SystemError("`appPath` config should not be empty");
        if (\PHP_VERSION_ID < 80100) throw new SystemError("Only PHP 8.1 and above is supported");

        $this->status = AppStatus::INITIALIZING;

        // Set app instance
        self::$__instance = &$this;

        // Benchmark start time
        $this->startTimeMs = defined('APP_START_TIME') ? APP_START_TIME : floor(microtime(true) * 1000);

        // Set cli state
        $this->isCli = is_cli();

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

        // Set error reporter
        $this->reporter = new Reporter;

        // Set router
        $this->router = new Router;

        // Set dependency resolver
        $this->resolver = new Resolver($this);

        // Set dependency injector
        $this->di = new DI($this);

        // Set event manager
        $this->eventManager = new LocalEventManager($this);

        // Set up error reporting
        $this->setUpErrorHandlers();

        // Load custom configs
        $this->loadConfigs();

        // Load custom providers
        $this->loadProviders();

        // Serializable closure secret
        $this->config->secret && SerializableClosure::setSecretKey($this->config->secret);
    }

    /**
     * [RESTRICTED]
     */
    function __serialize()
    {
        throw new SystemError("Serializing app instance is forbidden");
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

    /**
     * App is running in worker mode
     */
    public function isWorker(): bool
    {
        return $this->async && isset($this->worker);
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
    protected function loadConfig(string $config)
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
    protected function loadConfigs()
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
    protected function loadProviders()
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
    public function setUpErrorHandlers()
    {
        set_exception_handler(function (Throwable $e) {
            $this->reporter->leaveCrumbs("meta", ['type' => 'exception', 'env' => $this->env->value]);
            if ($e instanceof HttpException) {
                $response = $e->handler($this);
                !$this->isCli && !$this->async && $response->send($this->config->http->sendAndContinue);
            } else {
                $this->reporter->exception($e);
                !$this->isCli && !$this->async && Response::error(500, $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine())->send($this->config->http->sendAndContinue);
            }
        });

        set_error_handler(function (int $severity, string $message, string $file, ?int $line = 0) {
            if (in_array($severity, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING])) {
                return log_warning($message . "\n($file:$line)");
            }
            $this->reporter->leaveCrumbs("meta", ['type' => 'error', 'severity' => error_level($severity), 'env' => $this->env->value]);
            $this->reporter->exception(new \ErrorException($message, 0, $severity, $file, $line));
            !$this->isCli && !$this->async && Response::error(500, $message, 0, $file, $line)->send($this->config->http->sendAndContinue);
        });
    }

    /**
     * Set up shutdown handler
     * 
     * @param RequestInterface|RouteInterface $request, 
     * @param float $startTime
     */
    protected function addShutdownHandler(RequestInterface|RouteInterface &$request, float $startTime)
    {
        // Call only in sync mode to prevent memory leak
        if (!$this->async) {
            register_shutdown_function(function () use (&$request, $startTime) {
                if ($request) {
                    if ($request instanceof RequestInterface) {
                        // Leave logs for tracing
                        if ($this->config->logRequest && $request instanceof RequestInterface) {
                            $endTime = microtime(true) * 1000;
                            $this->logger->debug(sprintf("Request completed: id = %s, correlationId = %s, time = %s, durationMs = %s", $request->requestId(), $request->correlationId(), $endTime, round($endTime - $startTime, 2)));
                        }
                        // Save session
                        $request->session()?->save();
                    }
                    // Clean up request
                    $request = NULL;
                }
                $this->status = AppStatus::STOPPED;
            });
        }
    }

    /**
     * Add singleton
     * 
     * @inheritDoc
     */
    public function addSingleton(string $className, &$object): static
    {
        // Prevent setting global singletons for stateless classes
        // if app is running in async (stateless) mode
        if (
            $this->status === AppStatus::RUNNNIG
            && $this->async
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
        $this->throwIfRunning("Adding class binding while app is running is forbidden");

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
        $this->throwIfRunning("Adding a provider while app is running is forbidden");

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
        $this->throwIfRunning("Adding application middleware while app is running is forbidden");

        if ($middleware instanceof ServerMiddlewareInterface) {
            $this->middlewares[] = new PsrMiddleware($middleware, $this->config);
        } else {
            $this->middlewares[] = $middleware;
        }
        return $this;
    }

    /**
     * Set if app is running in async mode. E.g Using Swoole or other Event loops.
     *
     * @return  self
     */
    public function setAsync($async)
    {
        $this->throwIfRunning();

        $this->async = $async;
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
        $this->throwIfRunning("Setting up logger while app is running is forbidden");

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
        $this->throwIfRunning("Setting up router while app is running is forbidden");

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
        $this->throwIfRunning("Setting up reporter while app is running is forbidden");

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
        $this->throwIfRunning("Setting up dependency resolver while app is running is forbidden");

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
        $this->throwIfRunning("Setting up service discovery while app is running is forbidden");

        $this->serviceDiscovery = $serviceDiscovery;
        return $this;
    }

    /**
     * Set the value of eventManager
     *
     * @param  EventManagerInterface  $eventManager
     *
     * @return  self
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;

        return $this;
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
                (new Response(version: $request->version(), format: $this->config->http->responseFormat))->html(sprintf("Not found - %s %s", $request->method()->value, $request->path()), 404) : (new Response(format: $this->config->http->responseFormat))->html("Resource not found", 404);

            foreach (array_reverse(array_merge($this->middlewares, $this->router->process($request))) as $middleware) {
                $action = fn (RequestInterface|RouteInterface &$request): ResponseInterface => $middleware->process($request, new RequestHandler($action));
            }
            return ($action)($request);
        } catch (HttpException $e) {
            if ($this->async) throw $e;
            return $e->handler($this);
        } catch (Throwable $e) {
            if ($this->async) throw $e;
            $this->reporter->exception($e);
            return (new Response)->json(ResponseDto::fromError($e, $this->env, $this->config->version)->toArray(), 500);
        }
    }

    /**
     * Process application request
     *
     * @param RequestInterface|RouteInterface|null $request Custom request or route object
     * @return ResponseInterface
     */
    public function run(RequestInterface|RouteInterface|null $request = null): ResponseInterface
    {
        self::$__instance = &$this;

        $this->status = AppStatus::RUNNNIG;

        $startTime = $this->async ? microtime(true) * 1000 : $this->startTimeMs;

        // Set request
        $request = $request ?? Request::fromGlobal($this->config);

        // Set shutdown hook
        $this->addShutdownHandler($request, $startTime);

        // Leave logs for tracing
        if ($this->config->logRequest && $request instanceof RequestInterface) {
            $this->logger->debug(sprintf("Request started: id = %s, correlationId = %s, time = %s", $request->requestId(), $request->correlationId(), $startTime));
            $this->reporter->info([
                'time' => $startTime,
                'requestId' => $request->requestId(),
                'correlationId' => $request->correlationId(),
                'ip' => $request->ip(),
                'url' => $request->currentUrl(),
                'method' => $request->method(),
                'query' => $request->query()->all(),
                'body' => $request->request()->all(),
                'headers' => $request->request()->all(),
            ]);
        }

        // Process route request
        $response = $this->processMiddleware($request);

        // Leave logs for tracing
        if ($this->config->logRequest && $request instanceof RequestInterface) {
            $endTime = microtime(true) * 1000;
            $this->logger->debug(sprintf("Request completed: id = %s, correlationId = %s, time = %s, durationMs = %s", $request->requestId(), $request->correlationId(), $endTime, round($endTime - $startTime, 2)));
        }

        // Clean up request
        $request = NULL;

        return $response;
    }

    /**
     * Start async http server
     *
     * @param string $host Domain or IP address. E.g `www.myapp.com` or `112.33.4.55`
     * @param integer $port Remote port. Default: `80`
     * @param WorkerConfig|null $config
     * @return void
     */
    public function start(string $host, int $port = 80, WorkerConfig|null $config = null)
    {
        if (!extension_loaded('pcntl')) {
            exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
        }
        if (!extension_loaded('posix')) {
            exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
        }

        $config = $config ?? new WorkerConfig;

        // App running in async mode
        $this->setAsync(true);

        // Set up workerman
        Worker::$stopTimeout = 5;
        Worker::$logFile = $config->logFilePath ?: $this->config->tempPath . DIRECTORY_SEPARATOR . 'workerman.log';
        Worker::$statusFile = $config->statusFilePath ?: $this->config->appPath . DIRECTORY_SEPARATOR . 'workerman.status';
        Worker::$pidFile = $config->pidFilePath ?: $this->config->appPath . DIRECTORY_SEPARATOR . 'workerman.pid';
        Worker::$eventLoopClass = $this->getEventLooper($config->looper);

        //------- Add Main HTTP Worker -------//
        $this->setUpHttpWorker($host, $port, max([$config->httpWorkers, 1]));

        //------- Add Task Worker -------//
        $config->useTaskWorker && $this->setUpTaskWorker(max([$config->taskWorkers, 1]), $config->jobs);

        // TODO Add socket handler, socket request dto and socket data dto

        //----- Start event loop ------//
        Worker::$onMasterStop = function () {
            $this->logger->debug("Worker master process stopped");
        };
        @Worker::runAll();
    }

    /**
     * Setup application http worker
     * 
     * @param string $host
     * @param integer $port
     * @param integer $workers
     */
    private function setUpHttpWorker(string $host, int $port = 80, $workers = 1)
    {
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
        $this->worker->name = '[HTTP] ' . $this->config->name . ' v' . $this->config->version;
        $this->worker->count = $workers;

        if ($ssl) $this->worker->transport = 'ssl';

        $this->worker->onWorkerStart = function (Worker $worker) {
            $this->logger->info(sprintf("%s process %s started", $worker->name, $worker->id));

            // Add Custom Middlewares
            $this->addMiddleware(new StatelessCookieMiddleware($this->config));
            $this->addMiddleware(new StatelessSessionMiddleware($this->config, $this->config->sessionHandler ?? new WorkermanSessionHandler(
                new FileSessionHandler($this->config->getSessionConfigs()),
                $this->config->secret
            )));

            // Set status
            $this->status = AppStatus::RUNNNIG;
            $this->startTimeMs = floor(microtime(true) * 1000);

            // Register distributed service discovery if available
            if ($this->serviceDiscovery && $this->serviceDiscovery instanceof DistributedServiceDiscoveryInterface) {
                $this->serviceDiscovery->register();
            }

            // Add message handler
            $worker->onMessage = function (TcpConnection $connection, HttpRequest $request) {
                try {
                    $response = $this->run(Request::fromWorkerman($request, $this->config));
                    return $connection->send($response->toWorkerman());
                } catch (Throwable $e) {
                    $this->reporter->exception($e);
                    $response = (new Response)->json(ResponseDto::fromError($e, $this->env, $this->config->version)->toArray(), 500);
                    return $connection->send($response->toWorkerman());
                }
            };
        };
        $this->worker->onWorkerStop = function (Worker $worker) {
            $this->status = AppStatus::STOPPED;
            $this->logger->info(sprintf("%s process %s stopped", $worker->name, $worker->id));

            // Unregister distributed service discovery if available
            if ($this->serviceDiscovery && $this->serviceDiscovery instanceof DistributedServiceDiscoveryInterface) {
                $this->serviceDiscovery->unregister();
            }
        };
        $this->worker->onConnect = function (TcpConnection $connection) {
            $this->config->logRequest
                &&  $this->logger->info(sprintf("Connection to %s process %s from %s:%s started", $connection->worker->name, $connection->worker->id, $connection->getRemoteIp(), $connection->getRemotePort()));

            $this->reporter->leaveCrumbs("worker", [
                'name' => $connection->worker->name,
                'id' => $connection->worker->id,
                'user' => $connection->worker->user,
                'socket' => $connection->worker->getSocketName(),
            ]);
            $this->reporter->leaveCrumbs("connection", [
                'id' => $connection->id,
                'localAddress' => $connection->getLocalAddress(),
                'remoteAddress' => $connection->getRemoteAddress(),
            ]);
        };
        $this->worker->onClose = function (TcpConnection $connection) {
            $this->config->logRequest
                &&  $this->logger->info(sprintf("Connection to %s process %s from %s:%s closed", $connection->worker->name, $connection->worker->id, $connection->getRemoteIp(), $connection->getRemotePort()));
        };
        $this->worker->onError = function ($error) {
            $this->logger->error(sprintf("%s error: %s", $this->worker->name, strval($error)));
        };
    }

    /**
     * Setup Task worker
     * 
     * @param integer $workers
     * @param array<string,Task[]> $jobs
     */
    private function setUpTaskWorker(int $workers, array $jobs = [])
    {
        $this->taskWorker = new Worker('unix:///' . $this->config->tempPath . DIRECTORY_SEPARATOR . 'task_worker.sock');
        $this->taskWorker->name = '[Task] ' . $this->config->name . ' v' . $this->config->version;
        $this->taskWorker->transport = 'unix';
        $this->taskWorker->count = $workers;

        $this->taskWorker->onWorkerStart = function (Worker $worker) use ($jobs) {
            $this->logger->info(sprintf("%s process %s started", $worker->name, $worker->id));

            // Set status
            $this->status = AppStatus::RUNNNIG;
            $this->startTimeMs = floor(microtime(true) * 1000);

            // Add message handler
            $worker->onMessage = function (TcpConnection $connection, $data) {
                try {
                    if ($dto = TaskDto::parse($data)) {
                        if (
                            $dto->class
                            && class_exists($dto->class)
                            && is_subclass_of($dto->class, Task::class)
                        ) {
                            $task = $this->di->instantiate($dto->class, null, $dto->params);
                            if ($task instanceof Task) {
                                // Run task
                                if ($dto->async) {
                                    $connection->close();
                                    return $task->run();
                                } else {
                                    $result = $task->run();
                                    return $connection->send(serialize($result));
                                }
                            }
                        }
                        throw new Exception(sprintf("%s process %s: Bad request", $connection->worker->name, $connection->worker->id));
                    }
                    throw new Exception(sprintf("%s process %s: Access denied", $connection->worker->name, $connection->worker->id));
                } catch (Throwable $e) {
                    $this->reporter->exception($e);
                    $connection->close();
                }
            };

            // Start jobs
            if (!empty($jobs) && $worker->id == 0) {
                foreach ($jobs as $key => $list) {
                    if ($key == Cron::EVERY_SECOND->value) {
                        Timer::add(1, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } else if ($key == Cron::EVERY_MINUTE->value) {
                        Timer::add(60, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } else if ($key == Cron::HOURLY->value) {
                        Timer::add(60 * 60, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } else if ($key == Cron::DAILY->value) {
                        Timer::add(60 * 60 * 24, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } else if ($key == Cron::WEEKLY->value) {
                        Timer::add(strtotime("+1 week") - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } else if ($key == Cron::MONTHLY->value) {
                        Timer::add(strtotime("+1 month") - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } else if ($key == Cron::YEARLY->value) {
                        Timer::add(strtotime("+1 year") - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } else if (($time = strtotime($key)) > time()) {
                        Timer::add($time - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        }, [], false);
                    }
                }
            }
        };
        $this->taskWorker->onWorkerStop = function (Worker $worker) {
            $this->status = AppStatus::STOPPED;
            $this->logger->info(sprintf("%s process %s stopped", $worker->name, $worker->id));
        };
        $this->taskWorker->onConnect = function (TcpConnection $connection) {
            $this->config->logRequest
                &&  $this->logger->info(sprintf("Connection to %s process %s started", $connection->worker->name, $connection->worker->id));

            $this->reporter->leaveCrumbs("worker", [
                'name' => $connection->worker->name,
                'id' => $connection->worker->id,
                'user' => $connection->worker->user,
                'socket' => $connection->worker->getSocketName(),
            ]);
            $this->reporter->leaveCrumbs("connection", [
                'id' => $connection->id,
                'localAddress' => $connection->getLocalAddress(),
                'remoteAddress' => $connection->getRemoteAddress(),
            ]);
        };
        $this->taskWorker->onClose = function (TcpConnection $connection) {
            $this->config->logRequest
                &&  $this->logger->info(sprintf("Connection to %s process %s closed", $connection->worker->name, $connection->worker->id));
        };
        $this->taskWorker->onError = function ($error) {
            $this->logger->error(sprintf("%s error: %s", $this->taskWorker->name, strval($error)));
        };
    }

    ############################
    # HTTP Server Endpoints
    ############################

    /**
     * @inheritDoc
     */
    public function get(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::GET, $path);
    }

    /**
     * @inheritDoc
     */
    public function post(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::POST, $path);
    }

    /**
     * @inheritDoc
     */
    public function put(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::PUT, $path);
    }

    /**
     * @inheritDoc
     */
    public function patch(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::PATCH, $path);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::DELETE, $path);
    }

    /**
     * @inheritDoc
     */
    public function head(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::HEAD, $path);
    }

    /**
     * @inheritDoc
     */
    public function resource(string $path, string $controller)
    {
        $this->throwIfRunning();

        if (!in_array(ResourceControllerInterface::class, class_implements($controller))) {
            throw new SystemError("`$controller` does not implement " . ResourceControllerInterface::class);
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

    #################
    # Utils & Extras
    #################

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
        $instance = $this->di->instantiate(
            $className,
            $request,
            $params
        );

        // Add instance as singleton if supported
        if ($instance) {
            if ($instance instanceof SingletonInterface) {
                $this->addSingleton($className, $instance);
            } else if ($request && $instance instanceof SingletonStatelessInterface) {
                $request->addSingleton($className, $instance);
            }
        }
        return $instance;
    }

    /**
     * Get event looper class to be used
     * @param Looper $looper
     * @return string
     */
    protected function getEventLooper(Looper $looper)
    {
        if ($looper == Looper::EVENT && extension_loaded('event') && class_exists(\EventBase::class)) return Event::class;
        else if ($looper == Looper::EV && extension_loaded('ev') && class_exists(\Ev::class)) return Ev::class;
        else if ($looper == Looper::SWOOLE && extension_loaded('swoole') && class_exists(\Swoole\Event::class)) return Swoole::class;
        else if ($looper == Looper::UV && extension_loaded('uv') && class_exists(\UVLoop::class)) return Uv::class;
        else if ($looper == Looper::REACT && class_exists(\React\EventLoop\LoopInterface::class)) return Base::class;
        return Select::class;
    }

    /**
     * Throw error if app is running
     */
    public function throwIfRunning(string|null $message = null)
    {
        if ($this->status === AppStatus::RUNNNIG) {
            throw new SystemError($message ?: 'This action cannot be performed when app is running');
        }
    }

    /**
     * Throw error if app is not running in async mode
     */
    public function throwIfNotAsync()
    {
        if (!$this->async) {
            throw new SystemError('This action is only allowed when app is running in async mode');
        }
    }
}
