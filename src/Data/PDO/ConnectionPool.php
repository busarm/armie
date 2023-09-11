<?php

namespace Armie\Data\PDO;

use Armie\Configs\PDOConfig;
use Armie\Interfaces\SingletonInterface;
use Armie\Traits\Singleton;

/**
 * Connection Pool.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ConnectionPool implements SingletonInterface
{
    use Singleton;

    /**
     * @var int - Round robin
     */
    const PATTERN_RR = 1;
    /**
     * @var int - Random
     */
    const PATTERN_RAND = 2;

    /**
     * @var Connection[]
     */
    private static $pool = [];

    public function __construct(private PDOConfig $config, private int $pattern = self::PATTERN_RR)
    {
    }

    public function __sleep(): array
    {
        return  [
            'size',
            'pattern',
            'config',
        ];
    }

    public function __wakeup(): void
    {
    }

    /**
     * Get connection.
     *
     * @return Connection
     */
    public function get(): Connection
    {
        if (count(self::$pool) < $this->config->connectionPoolSize) {
            $connection = self::$pool[] = new Connection($this->config, count(self::$pool));
        } else {
            // Using round-robin pattern
            if ($this->pattern == self::PATTERN_RR) {
                $connection = self::$pool[] = array_shift(self::$pool);
            }
            // Using random pattern
            else {
                $connection = self::$pool[rand(0, $this->config->connectionPoolSize - 1)];
            }
        }

        return $connection;
    }

    public function __destruct()
    {
        self::$pool = [];
    }
}
