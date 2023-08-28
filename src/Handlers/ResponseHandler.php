<?php

namespace Armie\Handlers;

use Armie\Enums\ResponseFormat;
use Armie\Interfaces\Arrayable;
use Armie\Interfaces\ResponseHandlerInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Promise;
use Armie\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\StreamInterface;
use Traversable;

use function Armie\Helpers\await;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
final class ResponseHandler implements ResponseHandlerInterface
{
    public function __construct(private mixed $data, private $version = '1.1', private ResponseFormat $format = ResponseFormat::JSON)
    {
    }

    public function handle(): ResponseInterface
    {
        if ($this->data !== false) {
            if ($this->data instanceof ResponseInterface) {
                $response = $this->data;
            } elseif ($this->data instanceof MessageResponseInterface) {
                $response = Response::fromPsr($this->data);
            } elseif ($this->data instanceof ResponseHandlerInterface) {
                $response = $this->data->handle();
            } else {
                $response = new Response(statusCode: 200, version: $this->version, format: $this->format);
                if ($this->data !== null) {
                    if ($this->data instanceof StreamInterface) {
                        $response->setBody($this->data);
                    } elseif ($this->data instanceof Promise) {
                        $response->setBody(json_encode(await($this->data)));
                    } elseif ($this->data instanceof Traversable) {
                        $response->setBody(json_encode(iterator_to_array($this->data)));
                    } elseif ($this->data instanceof Arrayable) {
                        $response->setParameters($this->data->toArray());
                    } elseif (is_array($this->data) || is_object($this->data)) {
                        $response->setBody(Stream::create(json_encode($this->data)));
                    } elseif ($this->data !== true) {
                        $response->html(Stream::create(strval($this->data)), 200);
                    }
                }
            }
        } else {
            $response = new Response(statusCode: 404, version: $this->version, format: $this->format);
        }

        return $response;
    }
}
