<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Bags\Attribute;
use Busarm\PhpMini\Bags\Query;
use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Enums\HttpMethod;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Loader;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Server;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class LocalService extends BaseService
{
    public function __construct(private RequestInterface $request)
    {
        parent::__construct($request);
    }

    /**
     * Call service
     * 
     * @param ServiceRequestDto $dto
     * @return mixed
     */
    public function call(ServiceRequestDto $dto)
    {
        $path = $this->get($dto->name);
        if (empty($path)) {
            throw new SystemError(self::class . ": Location for client $dto->name not found");
        }

        $path = is_dir($path) ? $path . '/index.php' : $path;
        if (!file_exists($path)) {
            throw new SystemError(self::class . ": Client $dto->name App file not found: $path");
        }

        if ($dto->name == $this->getCurrentServiceName()) {
            throw new SystemError(self::class . ": Circular request to current service `$dto->name` not allowed");
        }

        $server[Server::HEADER_SERVICE_NAME] = $dto->name;
        return Loader::require($path, [
            'request' =>
            Request::fromUrl(
                $this->request->baseUrl() . '/' . $dto->route,
                match ($dto->type) {
                    ServiceType::CREATE => HttpMethod::POST,
                    ServiceType::UPDATE => HttpMethod::PUT,
                    ServiceType::DELETE => HttpMethod::DELETE,
                    default   => HttpMethod::GET,
                }
            )
                ->initialize(
                    new Query($dto->type == ServiceType::READ ? $dto->params : []),
                    new Attribute($dto->type == ServiceType::READ ? $dto->params : []),
                    $this->request->cookie(),
                    $this->request->session(),
                    $this->request->file(),
                    new Attribute(array_merge($this->request->server()->all(), $server)),
                    new Attribute(array_merge($this->request->header()->all(), $dto->headers)),
                    $this->request->content()
                )->toPsr()
        ]);
    }

    /**
     * Call service asynchronously
     * 
     * @param ServiceRequestDto $dto
     * @return mixed
     */
    public function callAsync(ServiceRequestDto $dto)
    {
        throw new SystemError("Async local service request not currently supported.");
    }
}
