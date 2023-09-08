<?php

namespace Armie\Handlers;

use Armie\Dto\ResponseDto;
use Armie\Interfaces\ErrorHandlerInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Response;

use function Armie\Helpers\app;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ErrorHandler implements ErrorHandlerInterface
{
    public function __construct()
    {
    }

    /**
     * @param \Throwable $throwable
     *
     * @return ResponseInterface
     */
    public function handle(\Throwable $throwable): ResponseInterface
    {
        app()->reporter->exception($throwable);

        return (new Response())->json(ResponseDto::fromError($throwable, app()->env, app()->config->version)->toArray(), 500);
    }
}
