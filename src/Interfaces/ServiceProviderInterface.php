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
     * @param ServiceRequestDto $dto
     * @return mixed
     */
    public function call(ServiceRequestDto $dto);

    /**
     * Call service asynchronously
     * 
     * @param ServiceRequestDto $dto
     * @return mixed
     */
    public function callAsync(ServiceRequestDto $dto);

    /**
     * Get service location for name
     * 
     * @param string $name
     * @return string
     */
    public function get($name);
}
