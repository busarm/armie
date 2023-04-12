<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Middlewares\PsrMiddleware;
use Closure;
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
use Busarm\PhpMini\Handlers\DependencyResolver;
use Busarm\PhpMini\Handlers\RequestHandler;
use Busarm\PhpMini\Interfaces\ContainerInterface;
use Busarm\PhpMini\Interfaces\Crud\CrudControllerInterface;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
use Busarm\PhpMini\Interfaces\ErrorReportingInterface;
use Busarm\PhpMini\Interfaces\HttpServerInterface;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;
use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Interfaces\SingletonStatelessInterface;
use Busarm\PhpMini\Traits\Container;

use Psr\Log\LoggerInterface;
use Psr\Http\Server\MiddlewareInterface as ServerMiddlewareInterface;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

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

    /** @var static App instance */
    public static $__instance = null;

    /** @var RouterInterface */
    public $router = null;

    /** @var LoggerInterface */
    public $logger = null;

    /** @var LoaderInterface */
    public $loader = null;

    /** @var ErrorReportingInterface */
    public $reporter = null;
    /**
     * @var ServiceDiscoveryInterface
     */
    public $serviceDiscovery = null;


    /** @var bool */
    public $isCli;

    /** @var int Request start time in milliseconds */
    public $startTimeMs;


    /** @var MiddlewareInterface[] */
    protected $middlewares = [];

    /** @var array */
    protected $bindings = [];

    /**
     * App current status
     *
     * @var int @see \Busarm\PhpMini\Enums\AppStatus
     */
    protected int $status = AppStatus::INITIALIZING;

    // SYSTEM HOOKS 
    protected Closure|null $startHook = null;
    protected Closure|null $completeHook = null;

    /**
     * @param Config $config App configuration object
     * @param string $env App environment. @see \Busarm\PhpMini\Enums\Env
     * @param bool $stateless If app is running in stateless mode. E.g Using Swoole. 
     * Do not use static variables for user specific data when runing on stateless mode.
     * 
     * Stateless mode does not support adding:
     * - Application-wide Singletons
     */
    public function __construct(public Config $config, public string $env = Env::LOCAL, public bool $stateless = false)
    {
        // Set app instance
        self::$__instance = &$this;

        // Set cli state
        $this->isCli = is_cli();

        // Benchmark start time
        $this->startTimeMs = defined('APP_START_TIME') ? APP_START_TIME : floor(microtime(true) * 1000);

        // Set error reporter
        $this->reporter = new ErrorReporter();

        // Set router
        $this->router = new Router;

        // Set Loader
        $this->loader = Loader::withConfig($this->config);

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
     * Set up error handlers
     */
    private function setUpErrorHandlers()
    {
        set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
            $this->reporter->reportError("Internal Server Error", $errstr, $errfile, $errline);
            !$this->isCli && $this->showMessage(500, sprintf("Error: %s", $errstr), $errno, $errline, $errfile);
        });
        set_exception_handler(function (Throwable $e) {
            if ($e instanceof SystemError) {
                $e->handler($this);
            } else if ($e instanceof HttpException) {
                $e->handler($this);
            } else {
                $this->reporter->reportException($e);
                $trace = array_map(function ($instance) {
                    return (new ErrorTraceDto($instance));
                }, $e->getTrace());
                !$this->isCli && $this->showMessage(500, sprintf("%s: %s", get_class($e), $e->getMessage()), $e->getCode(), $e->getLine(), $e->getFile(), $trace);
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
        // Set current app instance
        self::$__instance = &$this;
        $this->status = AppStatus::RUNNNIG;

        // Set request & response & router
        $request = $request ?? Request::fromGlobal();

        // Run start hook
        $this->triggerStartHook($request);

        // Set shutdown hook
        register_shutdown_function(function (App $app, RequestInterface|RouteInterface $request) {
            if ($app->status !== AppStatus::STOPPED) {
                if ($request instanceof RequestInterface) {
                    $response = new Response(version: $request->version(), format: $this->config->httpResponseFormat);
                } else {
                    $response = new Response(format: $this->config->httpResponseFormat);
                }
                $app->triggerCompleteHook($request, $response);
            }
        }, $this, $request);

        // Process route request
        $response = $this->processMiddleware($request);

        // Run complete hook
        $this->triggerCompleteHook($request, $response);

        $this->status = AppStatus::STOPPED;
        $request = NULL;

        return $response;
    }

    /**
     * Process middleware
     *
     * @param RequestInterface|RouteInterface $request
     * @return ResponseInterface
     */
    protected function processMiddleware(RequestInterface|RouteInterface $request): ResponseInterface
    {
        // Add default response handler
        $action = fn (RequestInterface|RouteInterface $request): ResponseInterface => $request instanceof RequestInterface ?
            (new Response(version: $request->version(), format: $this->config->httpResponseFormat))->html("Not found - " . ($request->method() . ' ' . $request->path()), 404) : (new Response(format: $this->config->httpResponseFormat))->html("Resource not found", 404);

        foreach (array_reverse(array_merge($this->middlewares, $this->router->process($request))) as $middleware) {
            $action = fn (RequestInterface|RouteInterface $request): ResponseInterface => $middleware->process($request, new RequestHandler($action));
        }

        return ($action)($request);
    }

    /**
     * Hook to run before processing request.
     * Use this do perform any pre-validations such as maintainence mode checkings.
     *
     * @param Closure $startHook. E.g fn (App $app, RequestInterface|RouteInterface $request, ResponseInterface $response)
     * @return void
     */
    public function beforeStart(Closure $startHook)
    {
        $this->startHook = $startHook;
    }

    /**
     * 
     * Process on start request hook
     *
     * @param RequestInterface|RouteInterface $request
     * @return void
     */
    private function triggerStartHook(RequestInterface|RouteInterface $request)
    {
        if ($this->startHook) {
            ($this->startHook)($this, $request);
        }
    }

    /**
     * Hook to run after processing request.
     * Use this to perform any custom post-request processing
     * 
     * This also registers a shutdown handler.
     * @see \register_shutdown_function
     *
     * @param Closure $completeHook. E.g fn (App $app, ResponseInterface $response)
     * @return void
     */
    public function afterComplete(Closure $completeHook)
    {
        $this->completeHook = $completeHook;
    }

    /**
     * Process on complete hook
     *
     * @param RequestInterface|RouteInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function triggerCompleteHook(RequestInterface|RouteInterface $request, ResponseInterface $response)
    {
        if ($this->completeHook) {
            ($this->completeHook)($this, $request, $response);
        }
    }

    /**
     * Instantiate class with dependencies
     * 
     * @param string $className
     * @param array<string, mixed> $params List of Custom params. (name => value) E.g [ 'request' => $request ]
     * @param RequestInterface|RouteInterface|null $request HTTP request/route instance
     * @return mixed
     */
    public function make(string $className, array $params = [], RequestInterface|RouteInterface|null $request = null)
    {
        // Get dependency resolver
        $resolver = $this->getBinding(DependencyResolverInterface::class, DependencyResolver::class);

        // Instantiate class
        $instance = DI::instantiate(
            $className,
            new $resolver($request),
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
     * @param string $className
     * @param object|null $object
     * @return self
     */
    public function addSingleton(string $className, &$object)
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
     * Add middleware
     *
     * @param MiddlewareInterface|ServerMiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface|ServerMiddlewareInterface $middleware)
    {
        if ($middleware instanceof ServerMiddlewareInterface) {
            $this->middlewares[] = new PsrMiddleware($middleware);
        } else {
            $this->middlewares[] = $middleware;
        }
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
     * @param ErrorReportingInterface $reporter
     * @return self
     */
    public function setErrorReporter(ErrorReportingInterface $reporter)
    {
        $this->reporter = $reporter;
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
     *
     * @param string $path HTTP path. e.g /home. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @param string $controller Application Controller class name e.g Home
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
     * @param string $errorLine 
     * @param string $errorFile 
     * @param array $errorTrace 
     * @return void
     */
    public function showMessage($status, $message = null, $errorCode = null, $errorLine = null, $errorFile = null,  $errorTrace = [])
    {
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
                $response->duration = (int)(floor(microtime(true) * 1000) - $this->startTimeMs);
            }

            (new Response)->json($response->toArray(), ($status >= 100 && $status < 600) ? $status : 500)->send($this->config->httpSendAndContinue);
        }
    }

    /**
     * Send HTTP JSON Response
     * @param int $status Status Code
     * @param BaseDto|array|object|string $data Data
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
                $response->duration = (int)(floor(microtime(true) * 1000) - $this->startTimeMs);
                $data = $response->toArray();
            }
        }

        (new Response)
            ->addHttpHeaders($headers)
            ->json($data, ($status >= 100 && $status < 600) ? $status : 500)
            ->send($this->config->httpSendAndContinue);
    }
}
