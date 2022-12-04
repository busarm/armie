<?php

namespace Busarm\PhpMini\Data\PDO;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ConnectionConfig
{
    public string|null $driver = null;
    public string|null $dsn = null;
    public string|null $database = null;
    public string|null $host = null;
    public int|null $port = null;
    public string|null $user = null;
    public string|null $password = null;
    public bool $persist = false;
    public bool $errorMode = false;
    public array $options = [];


    /**
     * Set the value of driver
     *
     * @return  self
     */
    public function setDriver(string|null $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Set the value of dsn
     *
     * @return  self
     */
    public function setDsn(string|null $dsn)
    {
        $this->dsn = $dsn;

        return $this;
    }

    /**
     * Set the value of host
     *
     * @return  self
     */
    public function setHost(string|null $host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set the value of port
     *
     * @return  self
     */
    public function setPort(int|null $port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Set the value of database name
     *
     * @return  self
     */
    public function setDatabase(string|null $database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setUser(string|null $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the value of password
     *
     * @return  self
     */
    public function setPassword(string|null $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set the value of persist
     *
     * @return  self
     */
    public function setPersist(bool $persist)
    {
        $this->persist = $persist;

        return $this;
    }

    /**
     * Set the value of errorMode
     *
     * @return  self
     */
    public function setErrorMode(bool $errorMode)
    {
        $this->errorMode = $errorMode;

        return $this;
    }

    /**
     * Set the value of options
     *
     * @return  self
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }
}
