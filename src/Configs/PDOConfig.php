<?php

namespace Armie\Configs;

use Armie\Interfaces\ConfigurationInterface;
use Armie\Traits\CustomConfig;

/**
 * Database Configuration
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class PDOConfig implements ConfigurationInterface
{

    use CustomConfig;

    /**
     * PDO connection dns
     *
     * @var string|null
     */
    public string|null $connectionDNS = null;

    /**
     * PDO connection driver. e.g mysql, sqlite, pgsql, sqlsvr, cubrid
     * @see https://www.php.net/manual/en/.drivers.php
     *
     * @var string|null
     */
    public string|null $connectionDriver = null;

    /**
     * PDO connection host. Host IP or Url
     *
     * @var string|null
     */
    public string|null $connectionHost = null;

    /**
     * PDO connection port
     *
     * @var int|null
     */
    public int|null $connectionPort = null;

    /**
     * PDO connection database name
     *
     * @var string|null
     */
    public string|null $connectionDatabase = null;

    /**
     * PDO connection username
     *
     * @var string|null
     */
    public string|null $connectionUsername = null;

    /**
     * PDO connection password
     *
     * @var string|null
     */
    public string|null $connectionPassword = null;

    /**
     * PDO connection persist
     *
     * @var bool
     */
    public bool $connectionPersist = false;

    /**
     * PDO connection activate error mode
     *
     * @var bool
     */
    public bool $connectionErrorMode = true;

    /**
     * PDO connection options
     *
     * @var array
     */
    public array $connectionOptions = [];

    /**
     * CUSTOM connection pool size
     *
     * @var int
     */
    public int $connectionPoolSize = 0;

    public function __construct()
    {
    }

    /**
     * Set PDO connection dns
     *
     * @param  string|null  $connectionDNS  PDO connection dns
     *
     * @return  self
     */
    public function setConnectionDNS($connectionDNS)
    {
        $this->connectionDNS = $connectionDNS;

        return $this;
    }

    /**
     * Set PDO connection driver. e.g mysql, sqlite, pgsql, sqlsvr, cubrid
     *
     * @param  string|null  $connectionDriver  PDO connection driver. e.g mysql, sqlite, pgsql, sqlsvr, cubrid
     *
     * @return  self
     */
    public function setConnectionDriver($connectionDriver)
    {
        $this->connectionDriver = $connectionDriver;

        return $this;
    }

    /**
     * Set pDO connection database name
     *
     * @param  string|null  $connectionDatabase  PDO connection database name
     *
     * @return  self
     */
    public function setConnectionDatabase($connectionDatabase)
    {
        $this->connectionDatabase = $connectionDatabase;

        return $this;
    }

    /**
     * Set PDO connection host. Host IP or Url
     *
     * @param  string|null  $connectionHost  PDO connection host. Host IP or Url
     *
     * @return  self
     */
    public function setConnectionHost($connectionHost)
    {
        $this->connectionHost = $connectionHost;

        return $this;
    }

    /**
     * Set PDO connection port
     *
     * @param  int|null  $connectionPort  PDO connection port
     *
     * @return  self
     */
    public function setConnectionPort($connectionPort)
    {
        $this->connectionPort = $connectionPort;

        return $this;
    }

    /**
     * Set PDO connection username
     *
     * @param  string|null  $connectionUsername  PDO connection username
     *
     * @return  self
     */
    public function setConnectionUsername($connectionUsername)
    {
        $this->connectionUsername = $connectionUsername;

        return $this;
    }

    /**
     * Set PDO connection password
     *
     * @param  string|null  $connectionPassword  PDO connection password
     *
     * @return  self
     */
    public function setConnectionPassword($connectionPassword)
    {
        $this->connectionPassword = $connectionPassword;

        return $this;
    }

    /**
     * Set PDO connection persist
     *
     * @param  bool  $connectionPersist  PDO connection persist
     *
     * @return  self
     */
    public function setConnectionPersist(bool $connectionPersist)
    {
        $this->connectionPersist = $connectionPersist;

        return $this;
    }

    /**
     * Set PDO connection activate error mode
     *
     * @param  bool  $connectionErrorMode  PDO connection activate error mode
     *
     * @return  self
     */
    public function setConnectionErrorMode(bool $connectionErrorMode)
    {
        $this->connectionErrorMode = $connectionErrorMode;

        return $this;
    }

    /**
     * Set PDO connection options
     *
     * @param  array  $connectionOptions  PDO connection options
     *
     * @return  self
     */
    public function setConnectionOptions(array $connectionOptions)
    {
        $this->connectionOptions = $connectionOptions;

        return $this;
    }

    /**
     * Set Custom connection pool size
     *
     * @param  int  $connectionPoolSize  CUSTOM connection pool size
     *
     * @return  self
     */ 
    public function setConnectionPoolSize(int $connectionPoolSize)
    {
        $this->connectionPoolSize = $connectionPoolSize;

        return $this;
    }
}
