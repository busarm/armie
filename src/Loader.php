<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Errors\LoaderError;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Throwable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Loader implements LoaderInterface
{

    /** @var string */
    protected string|null $appPath = null;
    /** @var string */
    protected string|null $viewPath = null;
    /** @var string */
    protected string|null $configPath = null;

    protected function __construct()
    {
    }

    /**
     * @return self
     */
    public static function withConfig(Config $config): self
    {
        $loader = new self;
        $loader->appPath = $config->appPath;
        $loader->viewPath = $config->viewPath;
        $loader->configPath = $config->configPath;
        return $loader;
    }

    /**
     * Fetches print result instead of sending it to the output buffer
     *
     * @param string $path
     * @param array $data
     * @return string The rendered content
     */
    public static function load($path, $data = null)
    {
        try {
            ob_start();
            if (is_array($data)) extract($data);
            include $path;
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Require file
     *
     * @param string $path
     * @param array $data
     * @return mixed
     */
    public static function require($path, $data = null)
    {
        if (is_array($data)) extract($data);
        return require $path;
    }

    /**
     * Load View File
     * 
     * @param $path
     * @param array $vars
     * @param bool $return
     * @throws LoaderError
     * @return string
     */
    public function view($path, $vars = array(), $return = false): ?string
    {
        $path = (str_starts_with($this->viewPath, $this->appPath) ? $this->viewPath : $this->appPath . DIRECTORY_SEPARATOR . $this->viewPath) . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($path)) {
            $content = self::load($path, $vars);
            if ($return) return $content;
            else echo $content;
        } else {
            if ($return) return null;
            else throw new LoaderError("View file '$path' not found");
        }
        return null;
    }

    /**
     * Load Config File
     * 
     * @param $path
     * @throws LoaderError
     * @return mixed
     */
    public function config($path): mixed
    {
        $path = (str_starts_with($this->configPath, $this->appPath) ? $this->configPath : $this->appPath . DIRECTORY_SEPARATOR . $this->configPath) . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($path)) {
            return require_once $path;
        } else {
            throw new LoaderError("Config file '$path' not found");
        }
    }
}
