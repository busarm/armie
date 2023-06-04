<?php

namespace Busarm\PhpMini\Interfaces;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ConfigurationInterface
{
    /**
     * Get config
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed Returns config value or default
     */
    public function get(string $name, $default = null);

    /**
     * Set config
     * 
     * @param string $name
     * @param mixed $value
     * @return mixed Returns config value
     */
    public function set(string $name, $value = null);
}
