<?php

namespace Busarm\PhpMini\Traits;

/**
 *  
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
trait CustomConfig
{
    private array $_custom   =   [];

    /**
     * @inheritDoc
     */
    public function get(string $name, $default = null)
    {
        $config = null;

        // Handle flattend query
        if (str_contains($name, '.')) {
            $keys = explode('.', $name);
            foreach ($keys as $index => $key) {
                if ($index == 0) {
                    $config = $this->_custom[$key] ?? $this->{$key} ?? null;
                } else if (isset($config)) {
                    if (isset($config[$key])) {
                        $config = $config[$key];
                    } else if (isset($config->{$key})) {
                        $config = $config->{$key};
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }
        } else {
            $config = $this->_custom[$name] ?? $this->{$name} ?? null;
        }

        return $config ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, $value = null)
    {
        return $this->_custom[$name] = $value;
    }
}
