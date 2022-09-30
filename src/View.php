<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Interfaces\ResponseHandlerInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Throwable;

use function Busarm\PhpMini\Helpers\app;

/**
 * Application View Provider 
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class View implements ResponseHandlerInterface
{
    /**
     * @param BaseDto|array|null $data
     * @param array $httpHeaders
     */
    public function __construct(protected BaseDto|array|null $data = null, protected $headers = array())
    {
    }

    /**
     * Fetches the view result intead of sending it to the output buffer
     *
     * @param BaseDto|array|null $data View Data
     * @param array $headers Http headers
     * @return string
     */
    public static function load(BaseDto|array|null $data = null, $headers = array())
    {
        $view = new static($data, $headers);
        try {
            $view->start();
            $view->render();
            return $view->end();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
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
     * @return void 
     */
    public function include($path, $return = false)
    {
        $params = [];

        if ($this->data instanceof CollectionBaseDto) $params = $this->data->toArray();
        else if ($this->data instanceof BaseDto) $params = $this->data->toArray();
        else if (is_array($this->data) || is_object($this->data)) $params = (array) $this->data;
        else if (is_string($this->data)) $params  = ['data' => $this->data];

        $content = app()->loader->view($path, $params, $return);

        if (!$return) echo $content;
        else return $content;
    }

    /**
     * 
     * Renders the view
     *
     * @return void
     */
    public abstract function render();

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
     * @param ResponseInterface|null $response
     * @return ResponseInterface|null
     */
    public function send($continue = false, ResponseInterface $response = null): ResponseInterface|null
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return null;
        }

        // clean buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // start buffer
        $this->start();
        $this->render();
        $content = $this->end();

        return ($response ?? (new Response))->addHttpHeaders($this->headers)->html($content, 200, $continue);
    }

    /**
     * @param ResponseInterface $response
     * @param bool $continue
     * @return self
     */
    public function handle(ResponseInterface $response, $continue = false): ResponseInterface|null
    {
        return $this->send($continue, $response);
    }
}
