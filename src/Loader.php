<?php

namespace Armie;

use Armie\Errors\LoaderError;
use Armie\Interfaces\LoaderInterface;
use Throwable;

/**
 * File or Class Loader
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class Loader implements LoaderInterface
{
    public function __construct(protected Config $config)
    {
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
     * Load View File
     * 
     * @param $path
     * @param array $vars
     * @param bool $return
     * 
     * @throws LoaderError
     * @return string
     */
    public function view($path, $vars = array(), $return = false): ?string
    {
        if (empty($this->config->viewPath)) throw new LoaderError("`viewPath` config should not be empty");

        $path = (str_starts_with($this->config->viewPath, $this->config->appPath) ?
            $this->config->viewPath :
            $this->config->appPath . DIRECTORY_SEPARATOR . $this->config->viewPath) . DIRECTORY_SEPARATOR . (is_file($path) ? $path : $path . '.php');

        if (file_exists($path)) {
            $content = self::load($path, $vars);
            if ($return) return $content;
            else echo $content;
        } else {
            if (!$return) throw new LoaderError("View file '$path' not found");
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
        if (empty($this->config->configPath)) throw new LoaderError("`configPath` config should not be empty");

        $path = (str_starts_with($this->config->configPath, $this->config->appPath) ?
            $this->config->configPath :
            $this->config->appPath . DIRECTORY_SEPARATOR . $this->config->configPath) . DIRECTORY_SEPARATOR . (is_file($path) ? $path : $path . '.php');
        if (file_exists($path)) {
            return require_once $path;
        } else {
            throw new LoaderError("Config file '$path' not found");
        }
    }

    ################# STATICS ##################

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
        return require($path);
    }
}
