<?php

namespace Busarm\PhpMini;

use Closure;
use Throwable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\ErrorTraceDto;
use Busarm\PhpMini\Dto\ResponseDto;
use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Enums\Verbose;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Exceptions\HttpException;
use Busarm\PhpMini\Exceptions\NotFoundException;
use Busarm\PhpMini\Interfaces\ErrorReportingInterface;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Middlewares\ResponseMiddleware;
use Symfony\Component\Console\Output\OutputInterface;

use function Busarm\PhpMini\Helpers\env;
use function Busarm\PhpMini\Helpers\is_cli;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class App
{
    /** @var MiddlewareInterface[] */
    public $middlewares = [];

    /** @var array */
    public $singletons = [];

    /** @var array */
    public $bindings = [];

    /** @var array */
    public $resolvers = [];

    /** @var array */
    public $configs = [];

    /** @var RequestInterface|mixed */
    public $request;

    /** @var ResponseInterface|mixed */
    public $response;

    /** @var RouterInterface */
    public $router;

    /** @var LoggerInterface */
    public $logger;

    /** @var LoaderInterface */
    public $loader;

    /** @var ErrorReportingInterface */
    public $reporter;

    /** @var int Request start time in milliseconds */
    public $startTimeMs;

    /** @var bool */
    public $isCli;

    // SYSTEM HOOKS 
    private Closure|null $startHook = null;
    private Closure|null $completeHook = null;

    /**
     * @param Config $config App configuration object
     * @param string $env App environment. @see Busarm\PhpMini\Enums\Env
     */
    public function __construct(public Config $config, public string $env = Env::LOCAL)
    {
        // Set current app instance
        Server::$__app = &$this;

        // Set cli state
        $this->isCli = is_cli();

        // Benchmark start time
        $this->startTimeMs = defined('APP_START_TIME') ? APP_START_TIME : floor(microtime(true) * 1000);

        // Set error reporter
        $this->reporter = new ErrorReporter();

        // Create request & response objects
        $this->request = Request::fromGlobal();
        $this->response = new Response();

        // Set router
        $this->router = Router::withRequest($this->request);

        // Set Loader
        $this->loader = Loader::withConfig($this->config);

        // Set logger
        $this->logger = new ConsoleLogger(new ConsoleOutput(match ($this->config->loggerVerborsity) {
            Verbose::QUIET => OutputInterface::VERBOSITY_QUIET,
            Verbose::NORMAL => OutputInterface::VERBOSITY_NORMAL,
            Verbose::VERBOSE => OutputInterface::VERBOSITY_VERBOSE,
            Verbose::VERY_VERBOSE => OutputInterface::VERBOSITY_VERY_VERBOSE,
            Verbose::DEBUG => OutputInterface::VERBOSITY_DEBUG
        }, true));

        // Set up error reporting
        $this->setUpErrorHandlers();

        // Add response middleware as the first in the chain
        $this->addMiddleware(new ResponseMiddleware());

        // Add app resolvers
        $this->addResolver(self::class, $this);
        $this->addResolver(Request::class, $this->request);
        $this->addResolver(RequestInterface::class, $this->request);
        $this->addResolver(Response::class, $this->response);
        $this->addResolver(ResponseInterface::class, $this->response);
        $this->addResolver(Router::class, $this->router);
        $this->addResolver(RouterInterface::class, $this->router);
        $this->addResolver(ErrorReporter::class, $this->reporter);
        $this->addResolver(ErrorReportingInterface::class, $this->reporter);
        $this->addResolver(ConsoleLogger::class, $this->logger);
        $this->addResolver(LoggerInterface::class, $this->logger);
        $this->addResolver(Loader::class, $this->loader);
        $this->addResolver(LoaderInterface::class, $this->loader);
    }

    ############################
    # Setup and Run
    ############################

    /**
     * Get custom config or set if not available
     * 
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function config(string $name, $value = null)
    {
        $config = $this->configs[$name] ?? null;
        if (is_null($config) || !is_null($value)) {
            $this->configs[$name] = $config = $value;
        }
        return $config;
    }

    /**
     * Load config file
     * 
     * @param string $config Config file name/path relative to Config Path (@see `Config::setConfigPath`)
     * @return self
     */
    public function loadConfig(string $config)
    {
        $configs = $this->loader->config($config);
        // Load configs into app
        if ($configs && is_array($configs)) {
            foreach ($configs as $key => $value) {
                $this->config($key, $value);
            }
        }
        return $this;
    }

    /**
     * Load config file
     * @param array $configs List of config file name/path relative to Config Path (@see `Config::setConfigPath`)
     */
    public function loadConfigs($configs = array())
    {
        if (!empty($configs)) {
            foreach ($configs as $config) {
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
            $this->showMessage(500, sprintf("Error: %s", $errstr), $errno, $errline, $errfile);
        });
        set_exception_handler(function (Throwable $e) {
            if ($e instanceof SystemError) {
                $e->handler();
            } else if ($e instanceof HttpException) {
                $e->handler();
            } else {
                $this->reporter->reportException($e);
                $trace = array_map(function ($instance) {
                    return (new ErrorTraceDto($instance))->toArray();
                }, $e->getTrace());
                $this->showMessage(500, sprintf("%s: %s", get_class($e), $e->getMessage()), $e->getCode(), $e->getLine(), $e->getFile(), $trace);
            }
        });
    }

    /**
     * Run application
     *
     * @param RequestInterface|null $request Custom request object
     * @return ResponseInterface
     */
    public function run(RequestInterface|null $request = null): ResponseInterface
    {
        // Set current app instance
        Server::$__app = &$this;

        // Set request & response & router
        $request = $request ?? (($req = $this->getBinding(RequestInterface::class)) ? $this->make($req, false) : $this->request);
        if ($request != $this->request) {
            $this->request = $request;
            $this->router->setPath($request->uri());
        }
        $this->response = ($res = $this->getBinding(ResponseInterface::class)) ? $this->make($res, false) : $this->response;

        // Run start hook
        if ($this->startHook) ($this->startHook)($this);

        // Set shutdown hook
        register_shutdown_function(function (App $app) {
            if ($app->completeHook) ($app->completeHook)($app);
        }, $this);
        
        // Initiate rerouting
        if ($this->router) {
            if ($this->processMiddleware($this, array_merge($this->middlewares, $this->router->process())) === false) {
                if ($this->router->getIsHttp()) {
                    throw new NotFoundException("Not found - " . ($this->router->getRequestMethod() . ' ' . $this->router->getRequestPath()));
                }
                throw new NotFoundException("Resource not found");
            }
        } else throw new SystemError("Router not configured. See `setRouter`");

        return $this->response;
    }

    /**
     * 
     * Process middleware
     *
     * @param self $app
     * @param MiddlewareInterface[] $middlewares
     * @param int $index
     * @return boolean|mixed
     */
    protected function processMiddleware(self $app, array $middlewares, $index = 0)
    {
        if (isset($middlewares[$index])) {
            return $middlewares[$index]->handle(app: $app, next: fn () => $this->processMiddleware($app, $middlewares, ++$index));
        }
        return false;
    }

    /**
     * Hook to run before processing request.
     * Use this do perform any pre-validations such as maintainence mode checkings.
     *
     * @param Closure $startHook
     * @return void
     */
    public function beforeStart(Closure $startHook)
    {
        $this->startHook = $startHook;
    }

    /**
     * Hook to run after processing request.
     * This registers a shutdown handler.
     * @see `register_shutdown_function`
     *
     * @param Closure $completeHook
     * @return void
     */
    public function afterComplete(Closure $completeHook)
    {
        $this->completeHook = $completeHook;
    }

    /**
     * Instantiate class with dependencies
     * 
     * @param string $className
     * @param bool $cache Save as singleton to be reused. Default: false
     * @return object
     */
    public function make($className, $cache = false)
    {
        if ($cache && ($singleton = $this->getSingleton($className))) return $singleton;
        else $instance = DI::instantiate($this, $className);
        // Add instance as singleton if supported
        if ($cache && ($instance instanceof SingletonInterface)) {
            $this->addSingleton($className, $instance);
        }
        return $instance;
    }

    /**
     * Add resolver
     *
     * @param string $name Class name, Interface name or Unique name of resolver
     * @param mixed $result Result of the resolution. e.g Class object
     * @return self
     */
    public function addResolver($name, &$result)
    {
        $this->resolvers[$name] = fn & () => $result;
        return $this;
    }

    /**
     * Get resolver
     *
     * @param string $className
     * @param object $default
     * @return self
     */
    public function getResolver($name)
    {
        return $this->resolvers[$name] ?? null;
    }

    /**
     * Add singleton
     * 
     * @param string $className
     * @param object|null $object
     * @return self
     */
    public function addSingleton($className, &$object)
    {
        $this->singletons[$className] = $object;
        return $this;
    }

    /**
     * Get singleton
     *
     * @param string $className
     * @param object $default
     * @return self
     */
    public function getSingleton($className, $default = null)
    {
        return $this->singletons[$className] ?? $default;
    }

    /**
     * Add interface binding. Binds interface to a specific class which implements it
     *
     * @param string $interfaceName
     * @param string $className
     * @return self
     */
    public function addBinding($interfaceName, $className)
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
     * @return self
     */
    public function getBinding($interfaceName, $default = null)
    {
        return $this->bindings[$interfaceName] ?? $default;
    }

    /**
     * Add Logger. Replaces existing
     *
     * @param LoggerInterface $logger
     * @return self
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Add middleware
     *
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
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
     * Add error reporter. Replaces existing
     * 
     * @param string $config
     * @return self
     */
    public function setErrorReporter(ErrorReportingInterface $reporter)
    {
        $this->reporter = $reporter;
        return $this;
    }


    ############################
    # Response
    ############################


    /**
     * Show Message
     * @param string $status Status Code
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
                    PHP_EOL . "success\t-\tfalse" .
                        PHP_EOL . "message\t-\t$message" .
                        PHP_EOL . "code\t-\t$errorCode" .
                        PHP_EOL . "version\t-\t" . $this->config->version .
                        PHP_EOL . "line\t-\t$errorLine" .
                        PHP_EOL . "path\t-\t$errorFile" .
                        PHP_EOL,
                    $errorTrace
                );
            } else {
                $this->logger->info(
                    PHP_EOL . "success\t-\ttrue" .
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
            $response->ip = $this->request->ip();

            // Show more info if not production
            if (!$response->success && $this->env !== Env::PROD) {
                $response->code = !empty($errorCode) ? $errorCode : null;
                $response->line = !empty($errorLine) ? $errorLine : null;
                $response->file = !empty($errorFile) ? $errorFile : null;
                $response->backtrace = !empty($errorTrace) ? $errorTrace : null;
                $response->duration = (floor(microtime(true) * 1000) - $this->startTimeMs);
            }

            $this->response->json($response->toArray(), ($status >= 100 && $status < 600) ? $status : 500, $this->config->httpSendAndContinue);
        }
    }

    /**
     * Send HTTP JSON Response
     * @param string $status Status Code
     * @param BaseDto|array|object|string $data Data
     * @param array $headers Headers
     * @return ResponseInterface|null
     */
    public function sendHttpResponse($status, $data = null, $headers = []): ResponseInterface|null
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
                $response->duration = (floor(microtime(true) * 1000) - $this->startTimeMs);
                $data = $response->toArray();
            }
        }

        return $this->response
            ->addHttpHeaders($headers)
            ->json($data, ($status >= 100 && $status < 600) ? $status : 500, $this->config->httpSendAndContinue);
    }
}
