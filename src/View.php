<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Errors\SystemError;
use Busarm\PhpMini\Interfaces\ResponseHandlerInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Stringable;
use Throwable;

use function Busarm\PhpMini\Helpers\view;

/**
 * Application View Provider 
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class View implements ResponseHandlerInterface, Stringable
{
    /**
     * @param BaseDto|array|null $data View Data
     * @param array $headers Http headers
     */
    public function __construct(protected BaseDto|array|null $data = null, protected $headers = array())
    {
    }

    /**
     * Fetches the view result instead of sending it to the output buffer
     *
     * @param BaseDto|array|null $data View Data
     * @param array $headers Http headers
     * @return string
     */
    public static function load(BaseDto|array|null $data = null, $headers = array())
    {
        $view = new self($data, $headers);
        try {
            $view->start();
            if ($content = $view->render()) echo $content;
            return $view->end();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Add http header 
     * 
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function addHeader($name, $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Fetch view file
     *
     * @param string $path
     * @param bool $return
     * @return string|null 
     */
    public function include($path, $return = false)
    {
        $params = [];

        if ($this->data instanceof CollectionBaseDto) $params = $this->data->toArray();
        else if ($this->data instanceof BaseDto) $params = $this->data->toArray();
        else if (is_array($this->data) || is_object($this->data)) $params = (array) $this->data;
        else if (is_string($this->data)) $params  = ['data' => $this->data];

        $content = view($path, $params, $return);

        if (!$return) {
            echo $content;
            return null;
        } else return $content;
    }

    /**
     * 
     * Renders the view - print out or return the view as string
     *
     * @return string|void
     */
    public function render()
    {
        throw new SystemError('`render` method not implemented');
    }

    /**
     * 
     * Get view data
     *
     * @return BaseDto|array|null
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Start output buffer
     * 
     * @return void
     */
    protected function start()
    {
        ob_start();
    }

    /**
     * End output buffer
     * 
     * @return string
     */
    protected function end()
    {
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * @param bool $continue
     * @return mixed
     */
    public function send($continue = false)
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }

        // clean buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        return $this->handle()->send($continue);
    }

    /**
     * @return ResponseInterface
     */
    public function handle(): ResponseInterface
    {
        $this->start();
        if ($content = $this->render()) echo $content;
        $content = $this->end();
        return (new Response)->addHttpHeaders($this->headers)->html($content, 200);
    }

    /**
     * Gets a string representation of the object
     * @return string Returns the `string` representation of the object.
     */
    public function __toString()
    {
        return strval($this->handle()->getBody());
    }
}
