<?php

namespace Armie\Interfaces;

use Armie\Dto\ServiceRequestDto;
use Armie\Dto\ServiceResponseDto;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
interface ServiceHandlerInterface
{
    /**
     * Call service.
     *
     * @param ServiceRequestDto $dto     Service Request DTO
     * @param RequestInterface  $request Current HTTP Request
     *
     * @return ServiceResponseDto
     */
    public function call(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto;

    /**
     * Call service asynchronously.
     *
     * @param ServiceRequestDto $dto     Service Request DTO
     * @param RequestInterface  $request Current HTTP Request
     *
     * @return ServiceResponseDto
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto;
}
