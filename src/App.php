<?php

namespace Armie;

use Armie\Bags\FileStore;
use Armie\Configs\ServerConfig;
use Armie\Dto\TaskDto;
use Armie\Enums\AppStatus;
use Armie\Enums\Cron;
use Armie\Enums\Env;
use Armie\Enums\HttpMethod;
use Armie\Enums\Looper;
use Armie\Enums\Verbose;
use Armie\Errors\SystemError;
use Armie\Exceptions\HttpException;
use Armie\Handlers\ErrorHandler;
use Armie\Handlers\EventHandler;
use Armie\Handlers\RequestHandler;
use Armie\Handlers\WorkerMaxRequestHandler;
use Armie\Handlers\WorkerQueueHandler;
use Armie\Handlers\WorkerSessionHandler;
use Armie\Interfaces\SingletonContainerInterface;
use Armie\Interfaces\Data\ResourceControllerInterface;
use Armie\Interfaces\DependencyResolverInterface;
use Armie\Interfaces\DistributedServiceDiscoveryInterface;
use Armie\Interfaces\ErrorHandlerInterface;
use Armie\Interfaces\EventHandlerInterface;
use Armie\Interfaces\HttpServerInterface;
use Armie\Interfaces\LoaderInterface;
use Armie\Interfaces\MiddlewareInterface;
use Armie\Interfaces\ProviderInterface;
use Armie\Interfaces\QueueHandlerInterface;
use Armie\Interfaces\ReportingInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;
use Armie\Interfaces\RouterInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Interfaces\SingletonInterface;
use Armie\Interfaces\SingletonStatelessInterface;
use Armie\Interfaces\SocketControllerInterface;
use Armie\Middlewares\PsrMiddleware;
use Armie\Middlewares\StatelessCookieMiddleware;
use Armie\Middlewares\StatelessSessionMiddleware;
use Armie\Service\RemoteClient;
use Armie\Tasks\Task;
use Armie\Traits\Container;
use Exception;
use Laravel\SerializableClosure\SerializableClosure;
use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as ServerMiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SessionHandlerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Workerman\Connection\ConnectionInterface;
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

use function Armie\Helpers\dispatch;
use function Armie\Helpers\error_level;
use function Armie\Helpers\is_cli;
use function Armie\Helpers\log_warning;
use function Armie\Helpers\serialize;

// TODO PSR Cache Interface
// TODO PSR Session Interface - replace SessionStoreInterface & SessionManager
// TODO Restructure folders to be self contained - class + it's interface
// TODO Add support for async, event, queuing for non-async mode

/**
 * Application Factory.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class App implements HttpServerInterface, SingletonContainerInterface
{
    use Container;

    /**
     * App started event. Only available if app is running in async mode. @see self::start
     */
    const EVENT_STARTED     = self::class . ':Started';
    /**
     * App stopped event. Only available if app is running in async mode. @see self::start
     */
    const EVENT_STOPPED     = self::class . ':Stopped';
    /**
     * App request running.
     */
    const EVENT_RUNNING     = self::class . ':Running';
    /**
     * App request completed.
     */
    const EVENT_COMPLETE    = self::class . ':Completed';

    /**
     * List of classes that must be handled as stateless when running in async mode.
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
        RouteInterface::class,
    ];

    /** @var ?static App instance */
    private static ?self $__instance = null;

    /** @var RouterInterface */
    public RouterInterface $router;

    /** @var LoggerInterface */
    public LoggerInterface $logger;

    /** @var LoaderInterface */
    public LoaderInterface $loader;

    /** @var ReportingInterface */
    public ReportingInterface $reporter;

    /** @var DependencyResolverInterface */
    public DependencyResolverInterface $resolver;

    /** @var DI */
    public DI $di;

    /**
     * @var ErrorHandlerInterface
     */
    public ErrorHandlerInterface $errorHandler;

    /**
     * @var EventHandlerInterface
     */
    public EventHandlerInterface $eventHandler;

    /**
     * @var ?SessionHandlerInterface
     */
    public ?SessionHandlerInterface $sessionHandler = null;

    /**
     * @var ?QueueHandlerInterface
     */
    public ?QueueHandlerInterface $queueHandler = null;

    /**
     * @var ?ServiceDiscoveryInterface
     */
    public ?ServiceDiscoveryInterface $serviceDiscovery = null;

    /** @var bool App is running in CLI mode */
    public bool $isCli;

    /** @var int|float App start time in milliseconds */
    public int|float $startTimeMs;

    /**
     * App is running in async mode.
     *
     * @var bool
     *           If app is running in asynchronous mode and supports asynchronous requests. E.g Using event loops such as Ev, Swoole.
     *           ### NOTE: Take caution when using static variables. TAKE CAUTION when using static variables.
     */
    public bool $async = false;

    /**
     * App current status.
     *
     * @var AppStatus
     */
    public AppStatus $status = AppStatus::STOPPED;

    /** @var ProviderInterface[] */
    protected $providers = [];

    /** @var MiddlewareInterface[] */
    protected $middlewares = [];

    /** @var array<string, string> */
    protected $bindings = [];

    /**
     * Application http worker address - when using event loop (async) mode.
     *
     * @var ?string
     */
    private ?string $httpWorkerAddress = null;

    /**
     * Application task worker address.
     *
     * @var ?string
     */
    private ?string $taskWorkerAddress = null;

    /**
     * Current application worker. Only available if app is running in async mode 
     *
     * @var ?Worker
     */
    private ?Worker $worker = null;

    /**
     * @param Config $config App configuration object
     * @param Env    $env    App environment
     */
    public function __construct(public Config $config, public Env $env = Env::LOCAL)
    {
        if (empty($this->config->appPath)) {
            throw new SystemError('`appPath` config should not be empty');
        }
        if (\PHP_VERSION_ID < 80100) {
            throw new SystemError('Only PHP 8.1 and above is supported');
        }

        $this->status = AppStatus::INITIALIZING;

        // Set app instance
        self::$__instance = &$this;

        // Benchmark start time
        $this->startTimeMs = defined('APP_START_TIME') ? APP_START_TIME : floor(microtime(true) * 1000);

        // Set cli state
        $this->isCli = is_cli();

        // Set Loader
        $this->loader = new Loader();

        // Set logger
        $this->logger = new ConsoleLogger(
            new ConsoleOutput(
                match ($this->config->loggerVerborsity) {
                    Verbose::QUIET        => OutputInterface::VERBOSITY_QUIET,
                    Verbose::NORMAL       => OutputInterface::VERBOSITY_NORMAL,
                    Verbose::VERBOSE      => OutputInterface::VERBOSITY_VERBOSE,
                    Verbose::VERY_VERBOSE => OutputInterface::VERBOSITY_VERY_VERBOSE,
                    Verbose::DEBUG        => OutputInterface::VERBOSITY_DEBUG,
                    default               => OutputInterface::VERBOSITY_NORMAL
                },
                true
            ),
            [
                LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::ALERT     => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::ERROR     => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::WARNING   => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::NOTICE    => OutputInterface::VERBOSITY_VERBOSE,
                LogLevel::INFO      => OutputInterface::VERBOSITY_VERY_VERBOSE,
                LogLevel::DEBUG     => OutputInterface::VERBOSITY_DEBUG,
            ],
            [
                LogLevel::EMERGENCY => 'error',
                LogLevel::ALERT     => 'error',
                LogLevel::CRITICAL  => 'error',
                LogLevel::ERROR     => 'error',
                LogLevel::WARNING   => 'comment',
                LogLevel::NOTICE    => 'question',
                LogLevel::INFO      => 'info',
                LogLevel::DEBUG     => 'info',
            ]
        );

        // Set error reporter
        $this->reporter = new Reporter();

        // Set router
        $this->router = new Router();

        // Set dependency resolver
        $this->resolver = new Resolver();

        // Set dependency injector
        $this->di = new DI();

        // Set event handler
        $this->errorHandler = new ErrorHandler();

        // Set event handler
        $this->eventHandler = new EventHandler();

        // Set up error reporting
        $this->setUpErrorHandlers();

        // Load custom configs
        $this->loadConfigs();

        // Load custom providers
        $this->loadProviders();

        // Serializable closure secret
        $this->config->secret && SerializableClosure::setSecretKey($this->config->secret);
    }

    public function __sleep(): array
    {
        throw new \BadMethodCallException('Cannot serialize ' . __CLASS__);
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__);
    }

    public function __set($key, $val)
    {
        throw new \BadMethodCallException('Cannot set dynamic value for ' . __CLASS__);
    }

    public function __get($key)
    {
        throw new \BadMethodCallException('Cannot get dynamic value for ' . __CLASS__);
    }

    /**
     * Get application instance.
     *
     * @return ?self
     */
    public static function &getInstance(): ?self
    {
        return self::$__instance;
    }

    //###########################
    // Setup and Run
    //###########################

    /**
     * Load custom config file.
     *
     * @param string $config File path relative to app path
     *
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
     * Load custom configs.
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
     * Load providers.
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
     * Set up error handlers.
     */
    public function setUpErrorHandlers()
    {
        set_exception_handler(function (Throwable $e) {
            $this->reporter->leaveCrumbs('meta', ['type' => 'exception', 'env' => $this->env->value]);
            if ($e instanceof HttpException) {
                $response = $e->handle($this);
                !$this->isCli && !$this->async && $response->send($this->config->http->sendAndContinue);
            } else {
                $response = $this->errorHandler->handle($e);
                !$this->isCli && !$this->async && $response->send($this->config->http->sendAndContinue);
            }
        });

        set_error_handler(function (int $severity, string $message, string $file, ?int $line = 0) {
            if (in_array($severity, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING])) {
                return log_warning(sprintf('%s (%s:%s)', $message, $file, $line ?? 1));
            } else {
                $this->reporter->leaveCrumbs('meta', ['type' => 'error', 'severity' => error_level($severity), 'env' => $this->env->value]);
                $this->reporter->exception(new \ErrorException($message, 0, $severity, $file, $line));
                !$this->isCli && !$this->async && Response::error(500, $message, 0, $file, $line)->send($this->config->http->sendAndContinue);
            }
        });
    }

    /**
     * Set up shutdown handler.
     *
     * @param RequestInterface|RouteInterface $request,
     * @param float                           $startTime
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
                            $duration = round($endTime - $startTime, 2);
                            $this->logger->info(sprintf('Request completed: id = %s, correlationId = %s, time = %s, durationMs = %s', $request->requestId(), $request->correlationId(), $endTime, $duration));
                        }
                        // Save session
                        $request->session()?->save();
                    }
                    // Clean up request
                    $request = null;
                }
                $this->status = AppStatus::STOPPED;
            });
        }
    }

    /**
     * Add singleton.
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
        ) {
            return $this;
        }

        $this->singletons[$className] = $object;

        return $this;
    }

    /**
     * Add interface binding. Binds interface to a specific class which implements it.
     *
     * @param string $interfaceName
     * @param string $className
     *
     * @return self
     */
    public function addBinding(string $interfaceName, string $className)
    {
        $this->throwIfRunning('Adding class binding while app is running is forbidden');

        if (!in_array($interfaceName, class_implements($className))) {
            throw new SystemError("`$className` does not implement `$interfaceName`");
        }
        $this->bindings[$interfaceName] = $className;

        return $this;
    }

    /**
     * Get interface binding.
     *
     * @param string $interfaceName
     * @param string $default
     *
     * @return string|null
     */
    public function getBinding(string $interfaceName, string $default = null): string|null
    {
        return $this->bindings[$interfaceName] ?? $default;
    }

    /**
     * Add provider.
     *
     * @param ProviderInterface $provider
     *
     * @return self
     */
    public function addProvider(ProviderInterface $provider)
    {
        $this->throwIfRunning('Adding a provider while app is running is forbidden');

        $this->providers[] = $provider;

        return $this;
    }

    /**
     * Add middleware.
     *
     * @param MiddlewareInterface|ServerMiddlewareInterface $middleware
     *
     * @return self
     */
    public function addMiddleware(MiddlewareInterface|ServerMiddlewareInterface $middleware)
    {
        $this->throwIfRunning('Adding application middleware while app is running is forbidden');

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
     * @return self
     */
    public function setAsync($async)
    {
        $this->throwIfRunning();

        $this->async = $async;

        return $this;
    }

    /**
     * Set Logger.
     *
     * @param LoggerInterface $logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->throwIfRunning('Setting up logger while app is running is forbidden');

        $this->logger = $logger;

        return $this;
    }

    /**
     * Set router.
     *
     * @param RouterInterface $router
     *
     * @return self
     */
    public function setRouter(RouterInterface $router)
    {
        $this->throwIfRunning('Setting up router while app is running is forbidden');

        $this->router = $router;

        return $this;
    }

    /**
     * Set error reporter.
     *
     * @param ReportingInterface $reporter
     *
     * @return self
     */
    public function setReporter(ReportingInterface $reporter)
    {
        $this->throwIfRunning('Setting up reporter while app is running is forbidden');

        $this->reporter = $reporter;

        return $this;
    }

    /**
     * Set dependency resolver.
     *
     * @param DependencyResolverInterface $resolver
     *
     * @return self
     */
    public function setDependencyResolver(DependencyResolverInterface $resolver)
    {
        $this->throwIfRunning('Setting up dependency resolver while app is running is forbidden');

        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Set service discovery.
     *
     * @param ServiceDiscoveryInterface $serviceDiscovery Service Discovery
     *
     * @return self
     */
    public function setServiceDiscovery(ServiceDiscoveryInterface $serviceDiscovery): self
    {
        $this->throwIfRunning('Setting up service discovery while app is running is forbidden');

        $this->serviceDiscovery = $serviceDiscovery;

        return $this;
    }

    /**
     * Set the value of eventHandler.
     *
     * @param EventHandlerInterface $eventHandler
     *
     * @return self
     */
    public function setEventHandler(EventHandlerInterface $eventHandler)
    {
        $this->eventHandler = $eventHandler;

        return $this;
    }


    /**
     * Set session Handler.
     *
     * @param SessionHandlerInterface $sessionHandler Session Handler
     *
     * @return self
     */
    public function setSessionHandler(SessionHandlerInterface $sessionHandler)
    {
        $this->sessionHandler = $sessionHandler;

        return $this;
    }

    /**
     * Set the value of queueHandler.
     *
     * @param QueueHandlerInterface $queueHandler
     *
     * @return self
     */
    public function setQueueHandler(QueueHandlerInterface $queueHandler)
    {
        $this->queueHandler = $queueHandler;

        return $this;
    }

    /**
     * Get application http worker address - when using event loop (async) mode.
     *
     * @return ?string
     */
    public function getHttpWorkerAddress(): ?string
    {
        return $this->httpWorkerAddress;
    }

    /**
     * Get application task worker address.
     *
     * @return ?string
     */
    public function getTaskWorkerAddress(): ?string
    {
        return $this->taskWorkerAddress;
    }

    /**
     * Set application task worker address. Use to set external task worker address.
     *
     * @param string $taskWorkerAddress Application task worker address.
     *
     * @return self
     */
    public function setTaskWorkerAddress(string $taskWorkerAddress)
    {
        $this->taskWorkerAddress = $taskWorkerAddress;

        return $this;
    }

    /**
     * Get current application worker.
     *
     * @return ?Worker
     */
    public function getWorker(): ?Worker
    {
        return $this->worker;
    }

    /**
     * Process middleware.
     *
     * @param RequestInterface|RouteInterface $request
     *
     * @return ResponseInterface
     */
    protected function processMiddleware(RequestInterface|RouteInterface $request): ResponseInterface
    {
        try {
            // Add default response handler
            $action = fn (RequestInterface|RouteInterface &$request): ResponseInterface => $request instanceof RequestInterface ?
                (new Response(version: $request->version(), format: $this->config->http->responseFormat))->html(sprintf('Not found - %s %s', $request->method()->value, $request->path()), 404) : (new Response(format: $this->config->http->responseFormat))->html('Resource not found', 404);

            foreach (array_reverse(array_merge($this->middlewares, $this->router->process($request))) as $middleware) {
                $action = fn (RequestInterface|RouteInterface &$request): ResponseInterface => $middleware->process($request, new RequestHandler($action));
            }

            return ($action)($request);
        } catch (HttpException $e) {
            if ($this->async) {
                throw $e;
            }

            return $e->handle($this);
        } catch (Throwable $e) {
            if ($this->async) {
                throw $e;
            }

            return $this->errorHandler->handle($e);
        }
    }

    /**
     * Process application request.
     *
     * @param RequestInterface|RouteInterface|null $request Custom request or route object
     *
     * @return ResponseInterface
     */
    public function run(RequestInterface|RouteInterface|null $request = null): ResponseInterface
    {
        self::$__instance = &$this;

        $this->status = AppStatus::RUNNNIG;

        $startTime = $this->async ? microtime(true) * 1000 : $this->startTimeMs;

        // Set request
        $request = $request ?? Request::fromGlobal($this->config);

        // Log Data
        $log = $request instanceof RequestInterface ? [
            'time'          => $startTime,
            'requestId'     => $request->requestId(),
            'correlationId' => $request->correlationId(),
            'ip'            => $request->ip(),
            'url'           => $request->currentUrl(),
            'method'        => $request->method()->value,
            'query'         => $request->query()->all(),
            'body'          => $request->request()->all(),
            'headers'       => $request->request()->all(),
        ] : [
            'time'          => $startTime,
        ];

        // Dispatch event
        dispatch(self::EVENT_RUNNING, $log);

        // Set shutdown hook
        $this->addShutdownHandler($request, $startTime);

        // Leave logs for tracing
        if ($this->config->logRequest && $request instanceof RequestInterface) {
            $this->logger->info(sprintf('Request started: id = %s, correlationId = %s, time = %s', $request->requestId(), $request->correlationId(), $startTime));
        }

        // Process route request
        $response = $this->processMiddleware($request);

        $endTime = microtime(true) * 1000;
        $duration = round($endTime - $startTime, 2);

        // Leave logs for tracing
        if ($this->config->logRequest && $request instanceof RequestInterface) {
            $this->logger->info(sprintf('Request completed: id = %s, correlationId = %s, time = %s, durationMs = %s', $request->requestId(), $request->correlationId(), $endTime, $duration));
        }

        // Clean up request
        $request = null;

        $this->status = AppStatus::COMPLETED;

        // Dispatch event
        $log['time'] = $endTime;
        $log['duration'] = $duration;
        dispatch(self::EVENT_COMPLETE, $log);

        return $response;
    }

    /**
     * Start asynchronous http workers. Auto start task and sockets workers.
     *
     * @param string            $host   Domain or IP address. E.g `www.myapp.com` or `112.33.4.55`
     * @param int               $port   Remote port. Default: `80`
     * @param ServerConfig|null $config
     *
     * @return void
     */
    public function start(string $host, int $port = 80, ServerConfig|null $config = null)
    {
        if (DIRECTORY_SEPARATOR == '/' && !extension_loaded('pcntl')) {
            exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
        }
        if (DIRECTORY_SEPARATOR == '/' && !extension_loaded('posix')) {
            exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
        }

        $config = $config ?? new ServerConfig();

        // App running in async mode
        $this->setAsync(true);

        // Set up workerman
        Worker::$stopTimeout = 5;
        Worker::$logFile = $config->logFilePath ?: $this->config->tempPath . DIRECTORY_SEPARATOR . 'workerman.log';
        Worker::$statusFile = $config->statusFilePath ?: $this->config->appPath . DIRECTORY_SEPARATOR . 'workerman.status';
        Worker::$pidFile = $config->pidFilePath ?: $this->config->appPath . DIRECTORY_SEPARATOR . 'workerman.pid';
        Worker::$eventLoopClass = $this->getEventLooper($config->looper);

        //------- Add Main HTTP Worker -------//
        $this->setUpHttpWorker($host, $port, $config);

        //------- Add Task Worker -------//
        $config->taskWorkers > 0 && $this->setUpTaskWorker($config);

        //------- Add Socket Workers -------//
        $this->setUpSocketWorkers($host, $config);

        //----- Start event loop ------//
        Worker::$onMasterStop = function () {
            $this->logger->debug('Worker master process stopped');
        };
        @Worker::runAll();
    }

    /**
     * Setup application http worker.
     *
     * @param string       $host
     * @param int          $port
     * @param ServerConfig $config
     */
    private function setUpHttpWorker(string $host, int $port, ServerConfig $config)
    {
        $count = max($config->httpWorkers, 1);

        // Set up SSL context.
        $ssl = $config->sslEnabled && $config->sslCertPath && $config->sslPkPath;
        $context = $ssl ? [
            'ssl' => [
                'local_cert'  => $config->sslCertPath,
                'local_pk'    => $config->sslPkPath,
                'verify_peer' => $config->sslVerifyPeer,
            ],
        ] : [];

        // Init Worker
        $this->httpWorkerAddress = ($ssl ? 'https://' : 'http://') . $host . ':' . $port;

        $worker = new Worker($this->httpWorkerAddress, $context);
        $worker->name = '[HTTP] ' . $this->config->name . ' v' . $this->config->version;
        $worker->count = $count;
        $worker->transport = $ssl ? 'ssl' : 'tcp';

        // Service Client
        $client = new RemoteClient(strtolower($this->config->name), $config->serverUrl ?? $worker->getSocketName());

        $worker->onWorkerStart = function (Worker $worker) use ($config, $client) {
            $this->status = AppStatus::STARTED;
            $this->startTimeMs = floor(microtime(true) * 1000);
            $this->worker = $worker;
            $this->logger->info(sprintf('%s (#%s) started', $worker->name, $worker->id));

            // Add Custom Middlewares
            $this->addMiddleware(new StatelessCookieMiddleware($this->config));
            $this->addMiddleware(new StatelessSessionMiddleware($this->config, $this->sessionHandler ?? new WorkerSessionHandler(
                new FileSessionHandler($this->config->getSessionConfigs()),
                $this->config->secret
            )));

            // Dispatch event
            if ($worker->id == 0) dispatch(self::EVENT_STARTED);

            // ----- Add Connection Handlers ----- //
            $worker->onMessage = function (TcpConnection $connection, HttpRequest $request) use ($config) {
                try {
                    self::$__instance = &$this;
                    $response = $this->run(Request::fromWorkerman($request, $this->config));
                    $connection->send($response->toWorkerman());
                } catch (HttpException $e) {
                    $response = $e->handle($this);
                    $connection->send($response->toWorkerman());
                    $connection->close();
                } catch (Throwable $e) {
                    $response = $this->errorHandler->handle($e);
                    $connection->send($response->toWorkerman());
                    $connection->close();
                }

                // Handle max requests
                WorkerMaxRequestHandler::handle($connection, $config->httpMaxRequests);
            };
            $worker->onConnect = function (TcpConnection $connection) {
                $this->config->logRequest
                    && $this->logger->info(sprintf('Connection to %s (#%s) from %s:%s started', $connection->worker->name, $connection->worker->id, $connection->getRemoteIp(), $connection->getRemotePort()));

                $this->reporter->leaveCrumbs('worker', [
                    'name'   => $connection->worker->name,
                    'id'     => $connection->worker->id,
                    'user'   => $connection->worker->user,
                    'socket' => $connection->worker->getSocketName(),
                ]);
                $this->reporter->leaveCrumbs('connection', [
                    'id'            => $connection->id,
                    'status'        => $connection->getStatus(),
                    'localAddress'  => $connection->getLocalAddress(),
                    'remoteAddress' => $connection->getRemoteAddress(),
                ]);
            };
            $worker->onClose = function (TcpConnection $connection) {
                $this->config->logRequest
                    && $this->logger->info(sprintf('Connection to %s (#%s) from %s:%s closed', $connection->worker->name, $connection->worker->id, $connection->getRemoteIp(), $connection->getRemotePort()));
            };
            $worker->onError = function (TcpConnection $connection, $id, $error) {
                $this->logger->error(sprintf('Connection to %s (#%s) failed: [%s] %s', $connection->worker->name, $connection->worker->id, $id, $error));
            };

            // Register distributed service discovery if available
            if ($this->serviceDiscovery && $this->serviceDiscovery instanceof DistributedServiceDiscoveryInterface) {
                try {
                    $this->serviceDiscovery->register($client);
                } catch (Throwable $e) {
                    $this->reporter->exception($e);
                }
            }

            // Add worker queue handler
            if (!$this->queueHandler) {
                try {
                    $this->queueHandler = new WorkerQueueHandler(
                        rate: 50,
                        store: new FileStore(
                            basePath: $this->config->tempPath . DIRECTORY_SEPARATOR . 'queue' . DIRECTORY_SEPARATOR . 'worker-' . $worker->id,
                            key: $this->config->secret,
                            async: false
                        )
                    );
                    // Run pending queue
                    $this->queueHandler->run();
                } catch (Throwable $e) {
                    $this->reporter->exception($e);
                }
            }
        };
        $worker->onWorkerStop = function (Worker $worker) use ($client) {
            $this->status = AppStatus::STOPPED;
            $this->logger->info(sprintf('%s (#%s) stopped', $worker->name, $worker->id));

            // Unregister distributed service discovery if available
            if ($this->serviceDiscovery && $this->serviceDiscovery instanceof DistributedServiceDiscoveryInterface) {
                try {
                    $this->serviceDiscovery->unregister($client);
                } catch (Throwable $e) {
                    $this->reporter->exception($e);
                }
            }

            // Dispatch event
            if ($worker->id == 0) dispatch(self::EVENT_STOPPED);
        };
        $worker->onWorkerReload = function (Worker $worker) {
            $this->logger->info(sprintf('%s (#%s) reloading', $worker->name, $worker->id));
        };
    }

    /**
     * Setup Task worker.
     *
     * @param ServerConfig $config
     */
    private function setUpTaskWorker(ServerConfig $config)
    {
        if ($this->taskWorkerAddress) {
            return;
        }

        // Only supported for unix
        if (DIRECTORY_SEPARATOR !== '/') {
            $this->logger->error('Failed to start task worker. Multiple workers is only available for unix systems. Alternatively, you can use separate start-up scripts.');

            return;
        }

        $this->taskWorkerAddress = 'unix:///' . $this->config->tempPath . DIRECTORY_SEPARATOR . 'task_worker.sock';

        $worker = new Worker($this->taskWorkerAddress);
        $worker->name = '[Task] ' . $this->config->name . ' v' . $this->config->version;
        $worker->transport = 'unix';
        $worker->count = max($config->taskWorkers, 1);

        $worker->onWorkerStart = function (Worker $worker) use ($config) {
            $this->status = AppStatus::STARTED;
            $this->startTimeMs = floor(microtime(true) * 1000);
            $this->worker = $worker;
            $this->logger->info(sprintf('%s (#%s) started', $worker->name, $worker->id));

            // ----- Add Connection Handlers ----- //
            $worker->onMessage = function (TcpConnection $connection, $data) use ($config) {
                try {
                    self::$__instance = &$this;

                    if (!($dto = TaskDto::parse($data))) {
                        throw new Exception(sprintf('%s (#%s): Access denied', $connection->worker->name, $connection->worker->id));
                    }
                    if (!($task = Task::parse($dto))) {
                        throw new Exception(sprintf('%s (#%s): Bad request', $connection->worker->name, $connection->worker->id));
                    }

                    // Run task
                    if ($dto->async) {
                        $connection->close();
                        $task->run();
                    } else {
                        $result = $task->run();
                        if (!feof($connection->getSocket())) {
                            $connection->send(serialize($result));
                        } else {
                            $connection->close();
                        }
                    }
                } catch (HttpException $e) {
                    $e->handle($this);
                    $connection->close();
                } catch (Throwable $e) {
                    $this->errorHandler->handle($e);
                    $connection->close();
                }

                // Handle max requests
                WorkerMaxRequestHandler::handle($connection, $config->httpMaxRequests);
            };
            $worker->onConnect = function (TcpConnection $connection) {
                $this->config->logRequest
                    && $this->logger->info(sprintf('Connection to %s (#%s) started', $connection->worker->name, $connection->worker->id));

                $this->reporter->leaveCrumbs('worker', [
                    'name'   => $connection->worker->name,
                    'id'     => $connection->worker->id,
                    'user'   => $connection->worker->user,
                    'socket' => $connection->worker->getSocketName(),
                ]);
                $this->reporter->leaveCrumbs('connection', [
                    'id'     => $connection->id,
                    'status' => $connection->getStatus(),
                ]);
            };
            $worker->onClose = function (TcpConnection $connection) {
                $this->config->logRequest
                    && $this->logger->info(sprintf('Connection to %s (#%s) closed', $connection->worker->name, $connection->worker->id));
            };
            $worker->onError = function (TcpConnection $connection, $id, $error) {
                $this->logger->error(sprintf('Connection to %s (#%s) failed: [%s] %s', $connection->worker->name, $connection->worker->id, $id, $error));
            };

            // ----- Start Jobs (on only the first worker) ----- //

            if ($worker->id == 0 && !empty($config->jobs)) {
                foreach ($config->jobs as $key => $list) {
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
                    } elseif ($key == Cron::EVERY_MINUTE->value) {
                        Timer::add(60, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } elseif ($key == Cron::HOURLY->value) {
                        Timer::add(60 * 60, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } elseif ($key == Cron::DAILY->value) {
                        Timer::add(60 * 60 * 24, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } elseif ($key == Cron::WEEKLY->value) {
                        Timer::add(strtotime('+1 week') - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } elseif ($key == Cron::MONTHLY->value) {
                        Timer::add(strtotime('+1 month') - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } elseif ($key == Cron::YEARLY->value) {
                        Timer::add(strtotime('+1 year') - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    } elseif (!is_numeric($key) && ($time = strtotime($key)) > time()) {
                        Timer::add($time - time(), function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        }, [], false);
                    } elseif (is_numeric($key)) {
                        Timer::add(((int) $key) ?: 1, function () use ($list) {
                            try {
                                foreach ($list as $task) {
                                    $task->run();
                                }
                            } catch (Throwable $e) {
                                $this->reporter->exception($e);
                            }
                        });
                    }
                }
            }
        };
        $worker->onWorkerStop = function (Worker $worker) {
            $this->status = AppStatus::STOPPED;
            $this->logger->info(sprintf('%s (#%s) stopped', $worker->name, $worker->id));
        };
        $worker->onWorkerReload = function (Worker $worker) {
            $this->logger->info(sprintf('%s (#%s) reloading', $worker->name, $worker->id));
        };
    }

    /**
     * Setup Socket workers.
     *
     * @param string       $host
     * @param ServerConfig $config
     */
    private function setUpSocketWorkers(string $host, ServerConfig $config)
    {
        // Only supported for unix
        if (DIRECTORY_SEPARATOR !== '/') {
            $this->logger->error('Failed to start socket workers. Multiple workers is only available for unix systems. Alternatively, you can use separate start-up scripts.');

            return;
        }

        // Set up SSL context.
        $ssl = $config->sslEnabled && $config->sslCertPath && $config->sslPkPath;
        $context = $ssl ? [
            'ssl' => [
                'local_cert'  => $config->sslCertPath,
                'local_pk'    => $config->sslPkPath,
                'verify_peer' => $config->sslVerifyPeer,
            ],
        ] : [];

        // Create workers
        foreach ($config->sockets as $port => $class) {
            $worker = new Worker('websocket://' . $host . ':' . $port, $context);
            $worker->name = '[Socket] ' . $this->config->name . ' v' . $this->config->version . " ($port)";
            $worker->transport = $ssl ? 'ssl' : 'tcp';

            $worker->onWorkerStart = function (Worker $worker) use ($class) {
                $this->status = AppStatus::STARTED;
                $this->startTimeMs = floor(microtime(true) * 1000);
                $this->logger->info(sprintf('%s (#%s) started', $worker->name, $worker->id));

                $controller = $this->di->instantiate($class);

                if ($controller && $controller instanceof SocketControllerInterface) {
                    $worker->onMessage = function (ConnectionInterface $connection, $data) use ($controller) {
                        try {
                            $controller->onMessage($connection, $data);
                        } catch (HttpException $e) {
                            $e->handle($this);
                        } catch (Throwable $e) {
                            $this->errorHandler->handle($e);
                        }
                    };
                    $worker->onConnect = function (TcpConnection $connection) use ($controller) {
                        $this->config->logRequest
                            && $this->logger->info(sprintf('Connection to %s (#%s) started', $connection->worker->name, $connection->worker->id));

                        $this->reporter->leaveCrumbs('worker', [
                            'name'   => $connection->worker->name,
                            'id'     => $connection->worker->id,
                            'user'   => $connection->worker->user,
                            'socket' => $connection->worker->getSocketName(),
                        ]);
                        $this->reporter->leaveCrumbs('connection', [
                            'id'     => $connection->id,
                            'status' => $connection->getStatus(),
                        ]);

                        try {
                            $controller->onConnect($connection);
                        } catch (HttpException $e) {
                            $e->handle($this);
                        } catch (Throwable $e) {
                            $this->errorHandler->handle($e);
                        }
                    };
                    $worker->onClose = function (TcpConnection $connection) use ($controller) {
                        $this->config->logRequest
                            && $this->logger->info(sprintf('Connection to %s (#%s) closed', $connection->worker->name, $connection->worker->id));

                        try {
                            $controller->onClose($connection);
                        } catch (HttpException $e) {
                            $e->handle($this);
                        } catch (Throwable $e) {
                            $this->errorHandler->handle($e);
                        }
                    };
                    $worker->onError = function (TcpConnection $connection, $id, $error) {
                        $this->logger->error(sprintf('Connection to %s (#%s) failed: [%s] %s', $connection->worker->name, $connection->worker->id, $id, $error));
                    };
                } else {
                    throw new SystemError("Failed to instantiate `$class`. Ensure it is a valid class that implements " . SocketControllerInterface::class);
                }
            };
            $worker->onWorkerStop = function (Worker $worker) {
                $this->status = AppStatus::STOPPED;
                $this->logger->info(sprintf('%s (#%s) stopped', $worker->name, $worker->id));
            };
        }
    }

    //###########################
    // HTTP Server Endpoints
    //###########################

    /**
     * @inheritDoc
     */
    public function get(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::GET->value, $path);
    }

    /**
     * @inheritDoc
     */
    public function post(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::POST->value, $path);
    }

    /**
     * @inheritDoc
     */
    public function put(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::PUT->value, $path);
    }

    /**
     * @inheritDoc
     */
    public function patch(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::PATCH->value, $path);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::DELETE->value, $path);
    }

    /**
     * @inheritDoc
     */
    public function head(string $path): RouteInterface
    {
        $this->throwIfRunning();

        return $this->router->createRoute(HttpMethod::HEAD->value, $path);
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

        $this->router->createRoute(HttpMethod::GET->value, "$path/list")->to($controller, 'list');
        $this->router->createRoute(HttpMethod::GET->value, "$path/paginate")->to($controller, 'paginatedList');
        $this->router->createRoute(HttpMethod::GET->value, "$path/{id}")->to($controller, 'get');
        $this->router->createRoute(HttpMethod::POST->value, "$path/bulk")->to($controller, 'createBulk');
        $this->router->createRoute(HttpMethod::POST->value, $path)->to($controller, 'create');
        $this->router->createRoute(HttpMethod::PUT->value, "$path/bulk")->to($controller, 'updateBulk');
        $this->router->createRoute(HttpMethod::PUT->value, "$path/{id}")->to($controller, 'update');
        $this->router->createRoute(HttpMethod::DELETE->value, "$path/bulk")->to($controller, 'deleteBulk');
        $this->router->createRoute(HttpMethod::DELETE->value, "$path/{id}")->to($controller, 'delete');
    }

    //################
    // Utils & Extras
    //################

    /**
     * Instantiate class with dependencies.
     *
     * @param class-string<T>                      $className
     * @param array<string, mixed>                 $params    List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @param RequestInterface|RouteInterface|null $request   HTTP request/route instance
     *
     * @return T
     *
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
            } elseif ($request && $instance instanceof SingletonStatelessInterface) {
                $request->addSingleton($className, $instance);
            }
        }

        return $instance;
    }

    /**
     * Get event looper class to be used.
     *
     * @param Looper $looper
     *
     * @return string
     */
    protected function getEventLooper(Looper $looper)
    {
        if ($looper == Looper::EVENT && extension_loaded('event') && class_exists(\EventBase::class)) {
            return Event::class;
        } elseif ($looper == Looper::EV && extension_loaded('ev') && class_exists(\Ev::class)) {
            return Ev::class;
        } elseif ($looper == Looper::SWOOLE && extension_loaded('swoole') && class_exists(\Swoole\Event::class)) {
            return Swoole::class;
        } elseif ($looper == Looper::UV && extension_loaded('uv') && class_exists(\UVLoop::class)) {
            return Uv::class;
        } elseif ($looper == Looper::REACT && class_exists(\React\EventLoop\LoopInterface::class)) {
            return Base::class;
        }

        return Select::class;
    }

    /**
     * Throw error if app is running.
     */
    public function throwIfRunning(string|null $message = null)
    {
        if ($this->status === AppStatus::RUNNNIG) {
            throw new SystemError($message ?: 'This action cannot be performed when app is running');
        }
    }

    /**
     * Throw error if app is not runinng with event loop.
     */
    public function throwIfNoEventLoop(string|null $message = null)
    {
        if (!$this->async && empty($this->httpWorkerAddress) || empty(Worker::getEventLoop())) {
            throw new SystemError($message ?: 'Event Loop is required for this action. Please run app in async mode. See App::start()');
        }
    }

    /**
     * Throw error if app has no task worker.
     */
    public function throwIfNoTaskWorker(string|null $message = null)
    {
        if (empty($this->taskWorkerAddress)) {
            throw new SystemError($message ?: 'Task worker is required for this action.');
        }
    }
}
