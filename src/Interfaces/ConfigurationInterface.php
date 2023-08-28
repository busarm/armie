<?php

namespace Armie\Interfaces;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ConfigurationInterface
{
    /**
     * Load configs.
     *
     * @param array<string,string|int|bool> $configs
     */
    public function load(array $configs);

    /**
     * Get config.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed Returns config value or default
     */
    public function get(string $name, $default = null);

    /**
     * Set config.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed Returns config value
     */
    public function set(string $name, $value = null);
}
