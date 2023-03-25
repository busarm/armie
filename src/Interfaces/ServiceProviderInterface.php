<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Dto\ServiceRequestDto;

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
     * @return mixed
     */
    public function call(ServiceRequestDto $dto, RequestInterface $request);

    /**
     * Call service asynchronously
     * 
     * @param ServiceRequestDto $dto  Service Request DTO
     * @param RequestInterface $request Current HTTP Request
     * @return mixed
     */
    public function callAsync(ServiceRequestDto $dto, RequestInterface $request);

    /**
     * Get service location for name
     * 
     * @param string $name
     * @return string|null
     */
    public function getLocation($name);
}
