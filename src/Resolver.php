<?php

namespace Armie;

use Armie\Configs\HttpConfig;
use Armie\Configs\PDOConfig;
use Armie\Dto\BaseDto;
use Armie\Enums\HttpMethod;
use Armie\Interfaces\ConfigurationInterface;
use Armie\Interfaces\DependencyResolverInterface;
use Armie\Interfaces\DistributedServiceDiscoveryInterface;
use Armie\Interfaces\ErrorHandlerInterface;
use Armie\Interfaces\EventHandlerInterface;
use Armie\Interfaces\LoaderInterface;
use Armie\Interfaces\QueueHandlerInterface;
use Armie\Interfaces\ReportingInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\Resolver\AuthResolver;
use Armie\Interfaces\Resolver\AuthUserResolver;
use Armie\Interfaces\Resolver\HttpConnectionResolver;
use Armie\Interfaces\ResponseInterface;
use Armie\Interfaces\RouteInterface;
use Armie\Interfaces\RouterInterface;
use Armie\Interfaces\ServiceDiscoveryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Workerman\Connection\ConnectionInterface;

use function Armie\Helpers\app;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Resolver implements ContainerInterface, DependencyResolverInterface
{
    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return !is_null($this->resolve($id));
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $className, RequestInterface|RouteInterface|null $request = null): mixed
    {
        return match ($className) {
            App::class => app(),
            Config::class, ConfigurationInterface::class => app()->config,
            PDOConfig::class             => app()->config->db,
            HttpConfig::class            => app()->config->http,
            RouterInterface::class       => app()->router,
            ReportingInterface::class    => app()->reporter,
            LoggerInterface::class       => app()->logger,
            LoaderInterface::class       => app()->loader,
            DI::class                    => app()->di,
            ErrorHandlerInterface::class => app()->errorHandler,
            EventHandlerInterface::class => app()->eventHandler,
            QueueHandlerInterface::class => app()->queueHandler,
            RouteInterface::class        => $request && $request instanceof RouteInterface ? $request : null,
            RequestInterface::class      => $request && $request instanceof RequestInterface ? $request : null,
            ServerRequestInterface::class, MessageRequestInterface::class => $request && $request instanceof RequestInterface ? $request->toPsr() : null,
            ResponseInterface::class         => $request && $request instanceof RequestInterface ? (new Response(version: $request->version(), format: app()->config->http->responseFormat)) : new Response(),
            MessageResponseInterface::class  => $request && $request instanceof RequestInterface ? (new Response(version: $request->version(), format: app()->config->http->responseFormat))->toPsr() : (new Response())->toPsr(),
            AuthResolver::class              => $request && $request instanceof RequestInterface ? $request->auth() : null,
            AuthUserResolver::class          => $request && $request instanceof RequestInterface ? $request->auth()?->getUser() : null,
            HttpConnectionResolver::class    => $request && $request instanceof RequestInterface ? $request->connection() : null,
            ConnectionInterface::class       => $request && $request instanceof RequestInterface ? $request->connection()?->get() : null,
            ServiceDiscoveryInterface::class, DistributedServiceDiscoveryInterface::class  => app()->serviceDiscovery,
            default => ($request ? $request->getSingleton($className) : null) ?: app()->getSingleton($className)
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
                } elseif ($request instanceof RouteInterface) {
                    return $instance->load($request->getParams(), true);
                }
            }
        }

        return $instance;
    }
}
