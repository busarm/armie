<?php

namespace Armie\Dto;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ServiceRegistryDto extends BaseDto
{
    public function __construct(
        public string $name,
        public string $url,
        public int $expiresAt = 0,
        public int $requestCount = 1,
    ) {
    }
}
