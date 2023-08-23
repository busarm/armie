<?php

namespace Armie\Service;

use Armie\Interfaces\ServiceClientInterface;

/**
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class RemoteClient implements ServiceClientInterface
{
    public function __construct(protected string $name, protected string $url)
    {
    }

    /**
     * Get service location. e.g path, url, ip etc.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the value of location
     */
    public function getLocation()
    {
        return $this->url;
    }

}
