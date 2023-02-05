<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
interface ServiceClientInterface
{
    /**
     * Get service name
     */
    public function getName();

    /**
     * Get service location. e.g path, url, ip etc.
     */
    public function getLocation();
}
