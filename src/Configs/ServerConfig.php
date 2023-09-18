<?php

namespace Armie\Configs;

use Armie\Enums\Looper;

/**
 * Async Server Configuration.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ServerConfig
{
    /**
     * Event loop type.
     *
     * @var Looper
     */
    public Looper $looper = Looper::DEFAULT;

    /**
     * Full path to worker's pid file.
     */
    public string|null $pidFilePath = null;

    /**
     * Full path to worker's status file.
     */
    public string|null $statusFilePath = null;

    /**
     * Full path to worker's log file.
     */
    public string|null $logFilePath = null;

    // ------------- SSL -----------------//

    /**
     * SSL is enabled.
     *
     * @var bool
     */
    public bool $sslEnabled = false;

    /**
     * SSL certificate path.
     *
     * @var string|null
     */
    public string|null $sslCertPath = null;

    /**
     * SSL primary key path.
     *
     * @var string|null
     */
    public string|null $sslPkPath = null;

    /**
     * SSL verify peer.
     *
     * @var bool
     */
    public bool $sslVerifyPeer = false;


    /**
     * Set event lool type.
     *
     * @param Looper $looper Event lool type
     *
     * @return static
     */
    public function setLooper(Looper $looper): static
    {
        $this->looper = $looper;

        return $this;
    }

    /**
     * Set full path to worker's pid file.
     *
     * @return static
     */
    public function setPidFilePath(string $pidFilePath): static
    {
        $this->pidFilePath = $pidFilePath;

        return $this;
    }

    /**
     * Set full path to worker's status file.
     *
     * @return static
     */
    public function setStatusFilePath(string $statusFilePath): static
    {
        $this->statusFilePath = $statusFilePath;

        return $this;
    }

    /**
     * Set full path to worker's log file.
     *
     * @return static
     */
    public function setLogFilePath(string $logFilePath): static
    {
        $this->logFilePath = $logFilePath;

        return $this;
    }

    /**
     * Set SSL is enabled.
     *
     * @param bool $sslEnabled SSL is enabled
     *
     * @return static
     */
    public function setSslEnabled(bool $sslEnabled)
    {
        $this->sslEnabled = $sslEnabled;

        return $this;
    }

    /**
     * Set SSL certificate path.
     *
     * @param string $sslCertPath SSL certificate path
     *
     * @return static
     */
    public function setSslCertPath(string $sslCertPath)
    {
        $this->sslCertPath = $sslCertPath;

        return $this;
    }

    /**
     * Set SSL primary key path.
     *
     * @param string $sslPkPath SSL primary key path
     *
     * @return static
     */
    public function setSslPkPath(string $sslPkPath)
    {
        $this->sslPkPath = $sslPkPath;

        return $this;
    }

    /**
     * Set SSL verify peer.
     *
     * @param bool $sslVerifyPeer SSL verify peer
     *
     * @return static
     */
    public function setSslVerifyPeer(bool $sslVerifyPeer)
    {
        $this->sslVerifyPeer = $sslVerifyPeer;

        return $this;
    }
}
