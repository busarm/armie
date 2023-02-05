<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Dto\ErrorTraceDto;
use Busarm\PhpMini\Dto\ResponseDto;
use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\ServiceClientInterface;
use Busarm\PhpMini\Service\LocalClient;
use Busarm\PhpMini\Service\LocalService;
use Busarm\PhpMini\Service\RemoteClient;
use Busarm\PhpMini\Service\RemoteService;
use Psr\Http\Message\ServerRequestInterface;

use Nyholm\Psr7\Uri;
use Throwable;

use function Busarm\PhpMini\Helpers\out;

/**
 * Server Instance for handling multi tenancy
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Server
{
    const HEADER_SERVER_NAME            = 'APP_SERVER_NAME';
    const HEADER_CORRELATION_ID         = 'APP_CORRELATION_ID';
    const HEADER_SERVICE_NAME           = 'APP_SERVICE_NAME';
    const HEADER_SERVICE_CLIENT_PREFIX  = 'APP_SERVICE_CLIENT_';

    /**
     * @var App[]
     */
    private $routeApps = [];
    /**
     * @var string[]
     */
    private $routePaths = [];
    /**
     * @var ServiceClientInterface[]
     */
    private $routeServices = [];
    /**
     * @var App[]
     */
    private $domainApps = [];
    /**
     * @var string[]
     */
    private $domainPaths = [];
    /**
     * @var ServiceClientInterface[]
     */
    private $domainServices = [];

    private string $correlationId;

    private ErrorReporter $reporter;

    public function __construct(public string $name)
    {
        $this->correlationId = md5(uniqid()) . '.' . microtime(true);
        $this->reporter = (new ErrorReporter);

        // Set up error handler
        set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
            $this->reporter->reportError("Internal Server Error", $errstr, $errfile, $errline);
            (new Response())
                ->setParameters(
                    (new ResponseDto)
                        ->setSuccess(false)
                        ->setErrorCode($errno)
                        ->setErrorLine($errline)
                        ->setErrorFile($errfile)
                        ->setMessage(sprintf("Error: %s", $errstr))
                        ->toArray()
                )->send();
        });
        set_exception_handler(function (Throwable $e) {
            $this->reporter->reportException($e);
            $trace = array_map(function ($instance) {
                return (new ErrorTraceDto($instance));
            }, $e->getTrace());
            (new Response())
                ->setParameters(
                    (new ResponseDto)
                        ->setSuccess(false)
                        ->setErrorCode($e->getCode())
                        ->setErrorLine($e->getLine())
                        ->setErrorFile($e->getFile())
                        ->setErrorTrace($trace)
                        ->setMessage(sprintf("%s: %s", get_class($e), $e->getMessage()))
                        ->toArray()
                )->send();
        });
    }

    /**
     * Map route to a particular app. (Not recommended for production)
     *
     * @param string $route Route path to app. e.g v1
     * @param App $app
     * @return self
     */
    public function addRouteApp($route, App $app): self
    {
        $this->routeApps[$route] = $app;
        return $this;
    }

    /**
     * Map list of: route to a particular app
     *
     * @param array<string,App> $list Array of `$route => App::class`. see `self::addRouteApp`
     * @return self
     */
    public function addRouteAppList(array $list): self
    {
        $this->routeApps = array_merge($this->routeApps, $list);
        return $this;
    }

    /**
     * Map route to a particular app path
     *
     * @param string $route Route path to app. e.g v1
     * @param string $path Path to app index. e.g ../myapp/public/index.php or ../myapp/public.
     * If path is a directory, '/index.php' will be appended to the path
     * @return self
     */
    public function addRoutePath($route, $path): self
    {
        $this->routePaths[$route] = $path;
        return $this;
    }

    /**
     * Map list of: route to a particular app path
     *
     * @param array<string,string> $list Array of `$route => $path`. see `self::addRoutePath`
     * @return self
     */
    public function addRoutePathList(array $list): self
    {
        $this->routePaths = array_merge($this->routePaths, $list);
        return $this;
    }

    /**
     * Map route to a particular service client
     *
     * @param string $route Route path to app. e.g v1
     * @param ServiceClientInterface $client Service client
     * @return self
     */
    public function addRouteService($route, ServiceClientInterface $client): self
    {
        $this->routeServices[$route] = $client;
        return $this;
    }

    /**
     * Map list of:  route to a particular service client
     *
     * @param array<string,ServiceClientInterface> $list Array of `$route => ServiceClientInterface::class`. see `self::addRouteService`
     * @return self
     */
    public function addRouteServiceList(array $list): self
    {
        $this->routeServices = array_merge($this->routeServices, $list);
        return $this;
    }

    /**
     * Map domain to a particular app. (Not recommended for production)
     *
     * @param string $domain Domain name. e.g myap.com, dev.myapp.com
     * @param App $app
     * @return self
     */
    public function addDomainApp($domain, App $app): self
    {
        $this->domainApps[$domain] = $app;
        return $this;
    }

    /**
     * Map list of: domain to a particular app
     *
     * @param array<string,App> $list Array of `$domain => App::class`. see `self::addRouteApp`
     * @return self
     */
    public function addDomainAppList(array $list): self
    {
        $this->domainApps = array_merge($this->domainApps, $list);
        return $this;
    }

    /**
     * Map domain to a particular app path
     *
     * @param string $domain Domain name. e.g myap.com, dev.myapp.com
     * @param string $path Path to app index. e.g ../myapp/public/index.php or ../myapp/public.
     * If path is a directory, '/index.php' will be appended to the path
     * @return self
     */
    public function addDomainPath($domain, $path): self
    {
        $this->domainPaths[$domain] = $path;
        return $this;
    }

    /**
     * Map list of domain to a particular app path
     *
     * @param array $list Array of `$domain => $path`. see `self::addDomainPath`
     * @return self
     */
    public function addDomainPathList(array $list): self
    {
        $this->domainPaths = array_merge($this->domainPaths, $list);
        return $this;
    }

    /**
     * Map domain to a particular service client
     *
     * @param string $route Route path to app. e.g v1
     * @param ServiceClientInterface $client Service client
     * @return self
     */
    public function addDomainService($route, ServiceClientInterface $client): self
    {
        $this->domainServices[$route] = $client;
        return $this;
    }

    /**
     * Map list of:  domain to a particular service client
     *
     * @param array<string,ServiceClientInterface> $list Array of `$domain => ServiceClientInterface::class`. see `self::addRouteService`
     * @return self
     */
    public function addDomainServiceList(array $list): self
    {
        $this->domainServices = array_merge($this->domainServices, $list);
        return $this;
    }

    /**
     * Run server
     *
     * @param ServerRequestInterface|null $request
     * @param bool $send Send response or False to 
     * @return ResponseInterface|null
     */
    public function run(ServerRequestInterface|null $request = null): ResponseInterface|null
    {
        $request = $request ? Request::fromPsr($request) : Request::fromGlobal();

        $request->server()->set(self::HEADER_SERVER_NAME, $this->name);
        $request->server()->set(self::HEADER_CORRELATION_ID, $this->correlationId);
        $request->server()->replace($this->getServiceHeaderMap());

        if (($response = $this->runRoute($request)) !== false) {
            return $response ? $response : (new Response())->setStatusCode(500);
        } else if (($response = $this->runDomain($request)) !== false) {
            return $response ?  $response : (new Response())->setStatusCode(500);
        }
        return (new Response())->setStatusCode(404);
    }

    /**
     * Run for route
     * 
     * @param RequestInterface $request
     * @return ResponseInterface|false|null False if failed
     */
    protected function runRoute(RequestInterface $request): ResponseInterface|false|null
    {
        foreach ($request->segments() as $route) {

            $uri = preg_replace("(^(/+)$route(/+))", "", $request->path());

            // Check route apps
            if (array_key_exists($route, $this->routeApps)) {
                return $this->routeApps[$route]->run($request->withUri(new Uri($request->baseUrl() . '/' . $uri)));
            }

            // Check route paths
            if (array_key_exists($route, $this->routePaths)) {
                $path = $this->routePaths[$route];
                $path = is_dir($path) ? $path . '/index.php' : $path;
                if (!file_exists($path)) {
                    throw new SystemError("App file not found: $path");
                }
                return Loader::require($path, [
                    'request' => $request->withUri(new Uri($request->baseUrl() . '/' . $uri))->toPsr()
                ]);
            }

            // Check route service client
            if (array_key_exists($route, $this->routeServices)) {
                $client = $this->routeServices[$route];
                return $this->runServiceClient($request, $client, $uri);
            }
        }

        return false;
    }

    /**
     * Run for domain
     * 
     * @param RequestInterface $request
     * @return ResponseInterface|false|null False if failed
     */
    protected function runDomain(RequestInterface $request): ResponseInterface|false|null
    {
        $domain = $request->domain();

        // Check domain apps
        if (array_key_exists($domain, $this->domainApps)) {
            return $this->domainApps[$domain]->run($request);
        }

        // Check domain paths
        if (array_key_exists($domain, $this->domainPaths)) {
            $path = $this->domainPaths[$domain];
            $path = is_dir($path) ? $path . '/index.php' : $path;
            if (!file_exists($path)) {
                throw new SystemError("App file not found: $path");
            }
            return Loader::require($path, ['request' => $request->toPsr()]);
        }


        // Check domain service client
        if (array_key_exists($domain, $this->domainServices)) {
            $client = $this->domainServices[$domain];
            return $this->runServiceClient($request, $client, $request->path());
        }

        return false;
    }

    /**
     * Run for service client
     * 
     * @param RequestInterface $request
     * @param ServiceClientInterface $client
     * @param string $uri
     * @return ResponseInterface|false|null False if failed
     */
    protected function runServiceClient(RequestInterface $request, ServiceClientInterface $client, string $uri): ResponseInterface|false|null
    {
        $type = match ($request->method()) {
            HttpMethod::POST => ServiceType::CREATE,
            HttpMethod::PUT, HttpMethod::PATCH => ServiceType::UPDATE,
            HttpMethod::DELETE => ServiceType::DELETE,
            default => ServiceType::READ,
        };
        $params = $request->method() == HttpMethod::GET ? $request->query()->all() : $request->request()->all();

        // Local Client Service
        if ($client instanceof LocalClient) {
            return (new LocalService($request))->call(
                (new ServiceRequestDto)
                    ->setName($client->getName())
                    ->setRoute($uri)
                    ->setType($type)
                    ->setParams($params)
                    ->setHeaders($request->header()->all())
            );
        }
        // Remote Client Service
        if ($client instanceof RemoteClient) {
            $response = (new RemoteService($request))->call(
                (new ServiceRequestDto)
                    ->setName($client->getName())
                    ->setRoute($uri)
                    ->setType($type)
                    ->setParams($params)
                    ->setHeaders([
                        self::HEADER_SERVER_NAME => $this->name,
                        self::HEADER_CORRELATION_ID => $this->correlationId,
                    ])
            );

            return Response::fromPsr($response);
        }

        return false;
    }

    /**
     * Get service map with header prefix
     * i.e [name => location]
     * @return array<string,string>
     */
    protected function getServiceHeaderMap()
    {
        $clients = [];
        foreach (array_merge(array_values($this->routeServices), array_values($this->domainServices)) as $client) {
            $clients[self::HEADER_SERVICE_CLIENT_PREFIX . strtoupper($client->getName())] = $client->getLocation();
        }
        return $clients;
    }
}
