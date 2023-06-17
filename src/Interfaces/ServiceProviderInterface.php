<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Dto\ServiceResponseDto;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface ServiceProviderInterface
{
    /**
     * Call service
     * 
     * @param ServiceRequestDto $dto  Service Request DTO
     * @param RequestInterface $request Current HTTP Request
     * @return ServiceResponseDto
     */
    public function call(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto;

    /**
     * Call service asynchronously
     * 
     * @param ServiceRequestDto $dto  Service Request DTO
     * @param RequestInterface $request Current HTTP Request
     * @return ServiceResponseDto
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request): ServiceResponseDto;

    /**
     * Get service location for name
     * 
     * @param string $name
     * @return string|null
     */
    public function getLocation($name);
}
