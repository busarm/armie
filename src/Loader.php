<?php

namespace Busarm\PhpMini;

use Exception;
use Busarm\PhpMini\Errors\LoaderError;
use Busarm\PhpMini\Interfaces\LoaderInterface;
use Throwable;

use function Busarm\PhpMini\Helpers\app;

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
     * Fetches print result intead of sending it to the output buffer
     *
     * @param string $path
     * @param array $data
     * @return string The rendered content
     */
    public static function load($path,  $data = null)
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
     * @param $path
     * @param array $vars
     * @param bool $return
     * @return string
     * @throws Exception
     */
    public function view($path, $vars = array(), $return = false): ?string
    {
        $path = $this->appPath . DIRECTORY_SEPARATOR . $this->viewPath . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($path)) {
            $content = $this->load($path, $vars);
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
     * @param $path
     * @throws Exception
     * @return mixed
     */
    public function config($path)
    {
        $path = $this->appPath . DIRECTORY_SEPARATOR . $this->configPath . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($path)) {
            return require_once $path;
        } else {
            throw new LoaderError("Config file '$path' not found");
        }
    }
}
