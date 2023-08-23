<?php

namespace Armie;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Interfaces\ResponseHandlerInterface;
use Armie\Interfaces\ResponseInterface;
use Stringable;

use function Armie\Helpers\view;

/**
 * Application View Provider 
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
abstract class View implements ResponseHandlerInterface, Stringable
{
    /**
     * @param BaseDto|array|null $data View Data
     * @param array $headers Http headers
     */
    public function __construct(protected BaseDto|array|null $data = null, protected $headers = array())
    {
    }

    /**
     * Get view data
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    final protected function get(string $name, mixed $default = null): mixed
    {
        if ($this->data) {
            if ($this->data instanceof BaseDto) return $this->data->get($name, $default);
            else if (is_array($this->data)) return $this->data[$name] ?? $default;
            else if (is_object($this->data)) return $this->data->{$name} ?? $default;
        }
        return $default;
    }

    /**
     * Start output buffer
     * 
     * @return void
     */
    final protected function start()
    {
        ob_start();
    }

    /**
     * End output buffer
     * 
     * @return string
     */
    final protected function end()
    {
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * 
     * Renders the view - print out or return the view as string
     *
     * @return string|void
     */
    abstract protected function render();

    /**
     * Fetch view file
     *
     * @param string $path
     * @param bool $return
     * @return string|null 
     */
    final protected function include($path, $return = false)
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
        return (new Response)->addHttpHeaders($this->headers)->html(strval($this), 200);
    }

    /**
     * Gets a string representation of the object
     * @return string Returns the `string` representation of the view.
     */
    public function __toString()
    {
        $this->start();
        if ($content = $this->render()) echo $content;
        return $content = $this->end();
    }
}
