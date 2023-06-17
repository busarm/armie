<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Data\PDO\ConnectionConfig;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Reporter;
use Busarm\PhpMini\Interfaces\ConfigurationInterface;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
use Busarm\PhpMini\Interfaces\ReportingInterface;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\Resolver\AuthResolver;
use Busarm\PhpMini\Interfaces\Resolver\AuthUserResolver;
use Busarm\PhpMini\Interfaces\Resolver\ServerConnectionResolver;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Interfaces\ServiceDiscoveryInterface;
use Busarm\PhpMini\Loader;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Response;
use Busarm\PhpMini\Route;
use Busarm\PhpMini\Router;
use Busarm\PhpMini\Service\BaseServiceDiscovery;
use Busarm\PhpMini\Resolvers\Auth;
use Busarm\PhpMini\Resolvers\ServerConnection;
use Nyholm\Psr7\Request as Psr7Request;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Workerman\Connection\ConnectionInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
            Router::class, RouterInterface::class => $this->app->router,
            Reporter::class, ReportingInterface::class => $this->app->reporter,
            ConsoleLogger::class, LoggerInterface::class => $this->app->logger,
            Loader::class, LoaderInterface::class => $this->app->loader,
            Route::class, RouteInterface::class => $request && $request instanceof RouteInterface ? $request : null,
            Request::class, RequestInterface::class => $request && $request instanceof RequestInterface ? $request : null,
            Psr7Request::class, ServerRequestInterface::class, MessageRequestInterface::class => $request && $request instanceof RequestInterface ? $request->toPsr() : null,
            Response::class, ResponseInterface::class => $request && $request instanceof RequestInterface ? (new Response(version: $request->version(), format: $this->app->config->http->responseFormat)) : new Response,
            Psr7Response::class, MessageResponseInterface::class => $request && $request instanceof RequestInterface ? (new Response(version: $request->version(), format: $this->app->config->http->responseFormat))->toPsr() : (new Response)->toPsr(),
            Auth::class, AuthResolver::class => $request && $request instanceof RequestInterface ? $request->auth() : null,
            AuthUserResolver::class => $request && $request instanceof RequestInterface ? $request->auth()?->getUser() : null,
            ServerConnection::class, ServerConnectionResolver::class  => $request && $request instanceof RequestInterface ? $request->connection() : null,
            ConnectionInterface::class  => $request && $request instanceof RequestInterface ? $request->connection()?->getConnection() : null,
            ConnectionConfig::class => (new ConnectionConfig())
                ->setDriver($this->app->config->db->connectionDriver)
                ->setDsn($this->app->config->db->connectionDNS)
                ->setHost($this->app->config->db->connectionHost)
                ->setPort($this->app->config->db->connectionPort)
                ->setDatabase($this->app->config->db->connectionDatabase)
                ->setUser($this->app->config->db->connectionUsername)
                ->setPassword($this->app->config->db->connectionPassword)
                ->setPersist($this->app->config->db->connectionPersist)
                ->setErrorMode($this->app->config->db->connectionErrorMode)
                ->setOptions($this->app->config->db->connectionOptions),
            BaseServiceDiscovery::class, ServiceDiscoveryInterface::class => $this->app->serviceDiscovery,
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
