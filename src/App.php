<?php

namespace Busarm\PhpMini;

use Closure;
use Throwable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\ResponseDto;
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

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class App
{
    /** @var static */
    public static $__instance;

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

    // SYSTEM HOOKS 
    private Closure|null $startHook = null;
    private Closure|null $completeHook = null;

    /**
     *
     * @param Config $config
     * @param string $env App environment. @see Busarm\PhpMini\Env
     */
    public function __construct(public Config $config, public string $env = Env::LOCAL)
    {
        self::$__instance = &$this;

        // Bootstrap files
        $this->bootstrap();

        // Benchmark start time
        $this->startTimeMs = defined('APP_START_TIME') ? APP_START_TIME : floor(microtime(true) * 1000);

        // Create request & response objects
        $this->request = Request::fromGlobal();
        $this->response = new Response();

        // Set error reporter
        $this->reporter = new ErrorReporter();

        // Set Loader
        $this->loader = new Loader();

        // Set router
        $this->router = new Router();

        // Set logger
        $this->logger = new ConsoleLogger(new ConsoleOutput(($this->env == Env::LOCAL || $this->env == Env::DEV) ? ConsoleOutput::VERBOSITY_DEBUG : ConsoleOutput::VERBOSITY_NORMAL, true));

        // Set up error reporting
        $this->setUpErrorHandlers();

        // Add response middleware as the first in the chain
        $this->addMiddleware(new ResponseMiddleware());

        // Add app resolvers
        $this->addResolver(self::class, fn () => $this);
        $this->addResolver(RequestInterface::class, fn () => $this->request);
        $this->addResolver(ResponseInterface::class, fn () => $this->response);
        $this->addResolver(RouterInterface::class, fn () => $this->router);
        $this->addResolver(ErrorReportingInterface::class, fn () => $this->reporter);
        $this->addResolver(LoggerInterface::class, fn () => $this->logger);
        $this->addResolver(LoaderInterface::class, fn () => $this->loader);
    }

    /**
     * Load bootstrap files 
     *
     * @return void
     */
    public static function bootstrap()
    {
        // Load bootstrap files
        require_once('./bootstrap/helpers.php');
    }

    ############################
    # Setup and Run
    ############################

    /**
     * Get custom config or set if not available
     * 
     * @param string $config
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
     * @param string $config Config file name/path relative to Config Path (@see `AppConfig::setConfigPath`)
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
     * Set up configs
     * @param array $configs
     */
    private function setUpConfigs($configs = array())
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
                    return [
                        'file' => $instance['file'] ?? null,
                        'line' => $instance['line'] ?? null,
                        'class' => $instance['class'] ?? null,
                        'function' => $instance['function'] ?? null,
                    ];
                }, $e->getTrace());
                $this->showMessage(500, sprintf("%s: %s", get_class($e), $e->getMessage()), $e->getCode(), $e->getLine(), $e->getFile(), $trace);
            }
        });
    }

    /**
     * Preflight Check
     *
     * @return void
     */
    private function preflight($method)
    {
        // Check for CORS access request
        if ($this->config->httpCheckCors == TRUE) {
            $headers = [];
            $allowed_cors_headers = $this->config->httpAllowedCorsHeaders ?? ['*'];
            $exposed_cors_headers = $this->config->httpExposedCorsHeaders ?? ['*'];
            $allowed_cors_methods = $this->config->httpAllowedCorsMethods ?? ['GET', 'POST', 'PUT', 'PATCH', 'OPTIONS', 'DELETE'];
            $max_cors_age = $this->config->httpCorsMaxAge ?? 3600;

            // Convert the config items into strings
            $allowed_headers = implode(', ', is_array($allowed_cors_headers) ? $allowed_cors_headers : []);
            $exposed_cors_headers = implode(', ', is_array($exposed_cors_headers) ? $exposed_cors_headers : []);
            $allowed_methods = implode(', ', is_array($allowed_cors_methods) ? $allowed_cors_methods : []);

            // If we want to allow any domain to access the API
            if ($this->config->httpAllowAnyCorsDomain == TRUE) {
                $headers['Access-Control-Allow-Origin'] = '*';
                $headers['Access-Control-Allow-Methods'] = $allowed_methods;
                $headers['Access-Control-Allow-Headers'] = $allowed_headers;
                $headers['Access-Control-Expose-Headers'] = $exposed_cors_headers;
                $headers['Access-Control-Allow-Max-Age'] = $max_cors_age;
            } else {
                // We're going to allow only certain domains access
                // Store the HTTP Origin header
                $origin = env('HTTP_ORIGIN') ?? env('HTTP_REFERER') ?? '';
                $allowed_origins = $this->config->httpAllowedCorsOrigins ?? [];
                // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
                if (is_array($allowed_origins) && in_array(trim($origin, "/"), $allowed_origins)) {
                    $headers['Access-Control-Allow-Origin'] = $origin;
                    $headers['Access-Control-Allow-Methods'] = $allowed_methods;
                    $headers['Access-Control-Allow-Headers'] = $allowed_headers;
                    $headers['Access-Control-Expose-Headers'] = $exposed_cors_headers;
                    $headers['Access-Control-Allow-Max-Age'] = $max_cors_age;
                }
            }

            // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
            if (strtolower($method) === 'options') {
                $headers['Cache-Control'] = "max-age=$max_cors_age";
                $this->sendHttpResponse(200, null, $headers);
            } else {
                $this->response->addHttpHeaders($headers);
            }
        } else {
            if (strtolower($method) === 'options') {
                // kill the response and send it to the client
                $this->showMessage(200, "Preflight Ok");
            }
        }
    }

    /**
     * Run application
     *
     * @return ResponseInterface
     */
    public function run(): ResponseInterface
    {
        // Set request & response bindings if exists
        $this->request = ($req = $this->getBinding(RequestInterface::class)) ? $this->make($req, false) : $this->request;
        $this->response = ($res = $this->getBinding(ResponseInterface::class)) ? $this->make($res, false) : $this->response;

        // Preflight Checking
        if (!is_cli()) {
            $this->preflight($this->router->getRequestMethod());
        }

        // Run start hook
        if ($this->startHook) ($this->startHook)($this);

        // Set shutdown hook
        register_shutdown_function(function (App $app) {
            if ($app->completeHook) ($app->completeHook)($app);
        }, $this);

        // Initiate rerouting
        if ($this->router) {
            if (!$this->processMiddleware($this, array_merge($this->middlewares, $this->router->process()))) {
                throw new NotFoundException("Not found - " . $this->router->getRequestMethod() . ' ' . $this->router->getRequestPath());
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
     * @return mixed
     */
    protected function processMiddleware(self $app, array $middlewares, $index = 0)
    {
        if (isset($middlewares[$index])) {
            return @$middlewares[$index]->handle(app: $app, next: fn () => $this->processMiddleware($app, $middlewares, ++$index));
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
     * @param Closure $resolver Annonymous resolver function returning the result of the resolution
     * @return self
     */
    public function addResolver($name, callable $resolver)
    {
        $this->resolvers[$name] = $resolver;
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
    public function addSingleton($className, &$object = null)
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
     */
    public function showMessage($status, $message = null, $errorCode = null, $errorLine = null, $errorFile = null,  $errorTrace = [])
    {
        if (is_cli()) {
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

            $this->response
                ->setParameters($response->toArray())
                ->setStatusCode(($status >= 100 && $status < 600) ? $status : 500)
                ->send();
        }
    }


    /**
     * Send HTTP JSON Response
     * @param string $status Status Code
     * @param BaseDto|array|object|string $data Data
     * @param array $headers Headers
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
                $response->duration = (floor(microtime(true) * 1000) - $this->startTimeMs);
                $data = $response->toArray();
            }
        }

        $this->response
            ->addHttpHeaders($headers)
            ->setParameters($data)
            ->setStatusCode(($status >= 100 && $status < 600) ? $status : 500)
            ->send();
    }
}
