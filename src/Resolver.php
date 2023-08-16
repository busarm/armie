<?php

namespace Armie;

use Armie\App;
use Armie\Config;
use Armie\Configs\HttpConfig;
use Armie\Configs\PDOConfig;
use Armie\Dto\BaseDto;
use Armie\Enums\HttpMethod;
use Armie\Handlers\EventHandler;
use Armie\Handlers\QueueHandler;
use Armie\Reporter;
use Armie\Interfaces\ConfigurationInterface;
use Armie\Interfaces\DependencyResolverInterface;
use Armie\Interfaces\DistributedServiceDiscoveryInterface;
use Armie\Interfaces\EventHandlerInterface;
use Armie\Interfaces\ReportingInterface;
use Armie\Interfaces\LoaderInterface;
use Armie\Interfaces\QueueHandlerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\Resolver\AuthResolver;
use Armie\Interfaces\Resolver\AuthUserResolver;
use Armie\Interfaces\Resolver\ServerConnectionResolver;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;
use Armie\Interfaces\RouterInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Armie\Loader;
use Armie\Request;
use Armie\Response;
use Armie\Route;
use Armie\Router;
use Armie\Resolvers\Auth;
use Armie\Resolvers\ServerConnection;
use Nyholm\Psr7\Request as Psr7Request;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Workerman\Connection\ConnectionInterface;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Resolver implements DependencyResolverInterface
{

    public function __construct(protected App $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $className, RequestInterface|RouteInterface|null $request = null): mixed
    {
        return match ($className) {
            App::class => $this->app,
            Config::class, ConfigurationInterface::class => $this->app->config,
            PDOConfig::class => $this->app->config->db,
            HttpConfig::class => $this->app->config->http,
            Router::class, RouterInterface::class => $this->app->router,
            Reporter::class, ReportingInterface::class => $this->app->reporter,
            ConsoleLogger::class, LoggerInterface::class => $this->app->logger,
            Loader::class, LoaderInterface::class => $this->app->loader,
            DI::class => $this->app->di,
            EventHandler::class, EventHandlerInterface::class => $this->app->eventHandler,
            QueueHandler::class, QueueHandlerInterface::class => $this->app->queueHandler,
            Route::class, RouteInterface::class => $request && $request instanceof RouteInterface ? $request : null,
            Request::class, RequestInterface::class => $request && $request instanceof RequestInterface ? $request : null,
            Psr7Request::class, ServerRequestInterface::class, MessageRequestInterface::class => $request && $request instanceof RequestInterface ? $request->toPsr() : null,
            Response::class, ResponseInterface::class => $request && $request instanceof RequestInterface ? (new Response(version: $request->version(), format: $this->app->config->http->responseFormat)) : new Response,
            Psr7Response::class, MessageResponseInterface::class => $request && $request instanceof RequestInterface ? (new Response(version: $request->version(), format: $this->app->config->http->responseFormat))->toPsr() : (new Response)->toPsr(),
            Auth::class, AuthResolver::class => $request && $request instanceof RequestInterface ? $request->auth() : null,
            AuthUserResolver::class => $request && $request instanceof RequestInterface ? $request->auth()?->getUser() : null,
            ServerConnection::class, ServerConnectionResolver::class  => $request && $request instanceof RequestInterface ? $request->connection() : null,
            ConnectionInterface::class  => $request && $request instanceof RequestInterface ? $request->connection()?->getConnection() : null,
            ServiceDiscoveryInterface::class, DistributedServiceDiscoveryInterface::class  => $this->app->serviceDiscovery,
            default => ($request ? $request->getSingleton($className) : null) ?: $this->app->getSingleton($className)
        };
    }

    /**
     * @inheritDoc
     */
    public function customize(mixed $instance, RequestInterface|RouteInterface|null $request = null): mixed
    {
        if ($request) {
            if ($instance instanceof BaseDto) {
                if ($request instanceof RequestInterface) {
                    return $instance->load(
                        $request->method() == HttpMethod::GET ?
                            $request->query()->all() :
                            $request->request()->all(),
                        true
                    );
                } else if ($request instanceof RouteInterface) {
                    return $instance->load($request->getParams(), true);
                }
            }
        }
        return $instance;
    }
}
