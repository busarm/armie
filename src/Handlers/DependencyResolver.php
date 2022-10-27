<?php

namespace Busarm\PhpMini\Handlers;

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\ErrorReporter;
use Busarm\PhpMini\Interfaces\DependencyResolverInterface;
use Busarm\PhpMini\Interfaces\ErrorReportingInterface;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;
use Busarm\PhpMini\Interfaces\RouterInterface;
use Busarm\PhpMini\Loader;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Response;
use Busarm\PhpMini\Route;
use Busarm\PhpMini\Router;
use Nyholm\Psr7\Request as Psr7Request;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

use function Busarm\PhpMini\Helpers\app;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class DependencyResolver implements DependencyResolverInterface
{
    /**
     */
    public function __construct(private RequestInterface|RouteInterface|null $request = null)
    {
    }

    /**
     * Resolve dependency for class name
     *
     * @param string $className
     *
     * @return mixed
     */
    public function resolveDependency(string $className): mixed
    {
        return match ($className) {
            App::class => app(),
            Config::class => app()->config,
            Router::class, RouterInterface::class => app()->router,
            ErrorReporter::class, ErrorReportingInterface::class => app()->reporter,
            ConsoleLogger::class, LoggerInterface::class => app()->logger,
            Loader::class, LoaderInterface::class => app()->loader,
            Route::class, RouteInterface::class => $this->request && $this->request instanceof RouteInterface ? $this->request : null,
            Request::class, RequestInterface::class => $this->request && $this->request instanceof RequestInterface ? $this->request : null,
            Psr7Request::class, ServerRequestInterface::class, MessageRequestInterface::class => $this->request && $this->request instanceof RequestInterface ? $this->request->toPsr() : null,
            Response::class, ResponseInterface::class => $this->request && $this->request instanceof RequestInterface ? (new Response(version: $this->request->version(), format: app()->config->httpResponseFormat)) : new Response,
            Psr7Response::class, MessageResponseInterface::class => $this->request && $this->request instanceof RequestInterface ? (new Response(version: $this->request->version(), format: app()->config->httpResponseFormat))->toPsr() : (new Response)->toPsr(),
            default => ($this->request ? $this->request->getSingleton($className) : null) ?: app()->getSingleton($className)
        };
    }

    /**
     * Customize dependency
     *
     * @param mixed $instance
     *
     * @return mixed
     */
    public function customizeDependency(mixed &$instance): mixed
    {
        if ($this->request) {
            if ($instance instanceof BaseDto) {
                if ($this->request instanceof RequestInterface) {
                    $instance->load($this->request->request()->all(), true);
                } else if ($this->request instanceof RouteInterface) {
                    $instance->load($this->request->getParams(), true);
                }
            } else if ($instance instanceof CollectionBaseDto) {
                if ($this->request instanceof RequestInterface) {
                    $instance->load($this->request->request()->all());
                } else if ($this->request instanceof RouteInterface) {
                    $instance->load($this->request->getParams());
                }
            }
        }
        return null;
    }
}
