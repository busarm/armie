<?php

namespace Armie;

use Armie\Dto\ErrorTraceDto;
use Armie\Dto\ResponseDto;
use Armie\Dto\ServiceRequestDto;
use Armie\Enums\Env;
use Armie\Enums\HttpMethod;
use Armie\Enums\ServiceType;
use Armie\Errors\SystemError;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\ServiceClientInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Service\LocalClient;
use Armie\Service\LocalService;
use Armie\Service\RemoteClient;
use Armie\Service\RemoteService;
use Psr\Http\Message\ServerRequestInterface;

use Nyholm\Psr7\Uri;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Throwable;

use const Armie\Constants\VAR_SERVER_NAME;

/**
 * PSR Request compatible server for handling multi tenancy
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Server
{
    /**
     * @var App[]
     */
    private $routeApps = [];
    /**
     * @var Response[]
     */
    private $routeStatics = [];
    /**
     * @var string[]
     */
    private $routePaths = [];
    /**
     * @var App[]
     */
    private $domainApps = [];

    /**
     * @var Response[]
     */
    private $domainStatics = [];

    /**
     * @var string[]
     */
    private $domainPaths = [];

    /**
     * @var ServiceDiscoveryInterface
     */
    private $serviceDiscovery = null;

    private Reporter $reporter;

    public function __construct(public string $name = 'Armie')
    {
        $this->reporter = (new Reporter);

        // Set up error handler
        set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
            $this->reporter->error($errstr);
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
            $this->reporter->exception($e);
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
     * [RESTRICTED]
     */
    public function __serialize()
    {
        throw new SystemError("Serializing server instance is forbidden");
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
     * Map route to a static response.
     *
     * @param string $route Route path to app. e.g v1
     * @param Response $response
     * @return self
     */
    public function addRouteStatic($route, Response $response): self
    {
        $this->routeStatics[$route] = $response;
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
     * Map domain to a static response.
     *
     * @param string $domain Domain name. e.g myap.com, dev.myapp.com
     * @param Response $response
     * @return self
     */
    public function addDomainStatic($domain, Response $response): self
    {
        $this->domainStatics[$domain] = $response;
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
     * Add service discovery for routes
     * 
     * @param ServiceDiscoveryInterface $serviceDiscovery Service Discovery
     * @param boolean $expose Expose service discovery to public.
     * @param string $exposePath Public path to expose service discovery. Default: `/discover`
     * @return self
     */
    public function addServiceDiscovery(ServiceDiscoveryInterface $serviceDiscovery, $expose = true, $exposePath = 'discover'): self
    {
        $this->serviceDiscovery = $serviceDiscovery;
        if ($expose) {
            $this->addRouteStatic($exposePath, (new Response($this->serviceDiscovery->getServiceClientsMap())));
        }
        return $this;
    }

    /**
     * Run server
     *
     * @param ServerRequestInterface|null $request
     * @return MessageResponseInterface
     */
    public function run(ServerRequestInterface|null $request = null): MessageResponseInterface
    {
        $request = $request ? Request::fromPsr($request) : Request::fromGlobal();

        $request->server()->set(VAR_SERVER_NAME, $this->name);

        if (($response = $this->runRoute($request)) !== false) {
            return $response ? $response->toPsr() : (new Response())->setStatusCode(500)->toPsr();
        } else if (($response = $this->runDomain($request)) !== false) {
            return $response ?  $response->toPsr() : (new Response())->setStatusCode(500)->toPsr();
        }
        return (new Response())->setStatusCode(404)->toPsr();
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

            $uri = preg_replace("/^(\/+)$route(\/+)/im", "", $request->path());

            // Check route apps
            if (array_key_exists($route, $this->routeApps)) {
                return $this->routeApps[$route]
                    ->setServiceDiscovery($this->serviceDiscovery)
                    ->run($request->withUri(new Uri($request->baseUrl() . '/' . $uri)));
            }

            // Check route static
            if (array_key_exists($route, $this->routeStatics)) {
                return $this->routeStatics[$route]->send();
            }

            // Check route paths
            if (array_key_exists($route, $this->routePaths)) {
                $path = $this->routePaths[$route];
                $path = is_dir($path) ? $path . '/index.php' : $path;
                if (!file_exists($path)) {
                    throw new SystemError("App file not found: $path");
                }
                return Loader::require($path, [
                    'request' => $request->withUri(new Uri($request->baseUrl() . '/' . $uri))->toPsr(),
                    'discovery' => $this->serviceDiscovery,
                ]);
            }

            // Check route service client
            if ($this->serviceDiscovery && ($client = $this->serviceDiscovery->getServiceClient($route))) {
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
            return $this->domainApps[$domain]
                ->setServiceDiscovery($this->serviceDiscovery)
                ->run($request);
        }

        // Check route static
        if (array_key_exists($domain, $this->domainStatics)) {
            return $this->domainStatics[$domain]->send();
        }

        // Check domain paths
        if (array_key_exists($domain, $this->domainPaths)) {
            $path = $this->domainPaths[$domain];
            $path = is_dir($path) ? $path . '/index.php' : $path;
            if (!file_exists($path)) {
                throw new SystemError("App file not found: $path");
            }
            return Loader::require($path, [
                'request' => $request->toPsr(),
                'discovery' => $this->serviceDiscovery,
            ]);
        }

        // Check route service client
        if ($this->serviceDiscovery && ($client = $this->serviceDiscovery->getServiceClient($domain))) {
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
        $uri = strval((new Uri($uri))->withQuery(strval($request->query())));
        $type = match ($request->method()) {
            HttpMethod::POST => ServiceType::CREATE,
            HttpMethod::PUT, HttpMethod::PATCH => ServiceType::UPDATE,
            HttpMethod::DELETE => ServiceType::DELETE,
            default => ServiceType::READ,
        };

        // Local Client Service
        if ($client instanceof LocalClient) {
            $response = (new LocalService($client->getName(), $client->getLocation(), $this->serviceDiscovery))->call(
                (new ServiceRequestDto)
                    ->setRoute($uri)
                    ->setType($type)
                    ->setParams($request->request()->all())
                    ->setHeaders($request->header()->all()),
                $request
            );
            return new Response($response->data, $response->code);
        }
        // Remote Client Service
        if ($client instanceof RemoteClient) {
            $response = (new RemoteService($client->getName(), $client->getLocation(), $this->serviceDiscovery))->call(
                (new ServiceRequestDto)
                    ->setRoute($uri)
                    ->setType($type)
                    ->setParams($request->request()->all()),
                $request
            );
            return new Response($response->data, $response->code);
        }

        return false;
    }
}
