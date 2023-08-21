<?php

namespace Armie\Handlers;

use Armie\App;
use Armie\Dto\ResponseDto;
use Armie\Interfaces\ErrorHandlerInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Response;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ErrorHandler implements ErrorHandlerInterface
{

    /**
     * @param App $app
     */
    public function __construct(private App $app)
    {
    }

    /**
     * @param \Throwable $throwable
     * @return ResponseInterface
     */
    public function handle(\Throwable $throwable): ResponseInterface
    {
        $this->app->reporter->exception($throwable);
        return (new Response)->json(ResponseDto::fromError($throwable, $this->app->env, $this->app->config->version)->toArray(), 500);
    }
}
