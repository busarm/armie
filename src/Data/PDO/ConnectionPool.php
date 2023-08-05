<?php

namespace Busarm\PhpMini\Data\PDO;

use Busarm\PhpMini\Configs\PDOConfig;
use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Traits\Singleton;

/**
 * Connection Pool
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ConnectionPool implements SingletonInterface
{
    use Singleton;

    /**
     * @var int - Round robin
     */
    const PATTERN_RR    = 1;
    /**
     * @var int - Random
     */
    const PATTERN_RAND  = 2;

    /**
     * @var Connection[]
     * // TODO remove from static
     */
    private static $pool = [];

    /**
     * @param int $size - Pool size
     * @inheritDoc
     */
    public function __construct(private PDOConfig $config, private int $size, private $pattern = self::PATTERN_RR)
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
     * Get connection
     *
     * @return Connection
     */
    public function get(): Connection
    {
        if (count(self::$pool) < $this->size) {
            $connection = self::$pool[] = new Connection($this->config, count(self::$pool));
        } else {
            // Using round-robin pattern
            if ($this->pattern == self::PATTERN_RR) {
                $connection =  self::$pool[] = array_shift(self::$pool);
            }
            // Using random pattern
            else {
                $connection =  self::$pool[rand(0, $this->size - 1)];
            }
        }
        return $connection;
    }

    public function __destruct()
    {
        self::$pool = [];
    }
}
