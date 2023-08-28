<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
interface ServiceClientInterface
{
    /**
     * Get service name.
     */
    public function getName();

    /**
     * Get service location. e.g path, url, ip etc.
     */
    public function getLocation();
}
