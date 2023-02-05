<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Interfaces\ServiceClientInterface;

/**
 * Error Reporting
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class LocalClient implements ServiceClientInterface
{
    public function __construct(private string $name, private string $path)
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
        return $this->path;
    }

}
