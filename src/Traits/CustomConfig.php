<?php

namespace Armie\Traits;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
trait CustomConfig
{
    private array $_custom = [];

    /**
     * Load configs.
     *
     * @param array<string,string|int|bool> $configs
     */
    public function load(array $configs)
    {
        foreach ($configs as $key => $config) {
            if (property_exists($this, $key)) {
                $this->{$key} = $config;
            } else {
                $this->set($key, $config);
            }
        }
    }

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
                } elseif (isset($config)) {
                    if (isset($config[$key])) {
                        $config = $config[$key];
                    } elseif (isset($config->{$key})) {
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
