<?php

namespace Armie\Service;

use Armie\Interfaces\ServiceClientInterface;

/**
 * Error Reporting
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class LocalClient implements ServiceClientInterface
{
    public function __construct(private string $name, private string $path)
    {
    }

    /**
     * Get service client name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get service client location. e.g path, url, ip etc.
     */
    public function getLocation()
    {
        return $this->path;
    }

}
