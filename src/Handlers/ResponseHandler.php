<?php

namespace Armie\Handlers;

use Armie\Data\DataObject;
use Armie\Dto\CollectionBaseDto;
use Armie\Enums\ResponseFormat;
use Armie\Interfaces\Arrayable;
use Armie\Interfaces\ResponseHandlerInterface;
use Armie\Interfaces\ResponseInterface;
use Armie\Promise;
use Armie\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\StreamInterface;
use Stringable;

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
                    // Stream, Stringable or Resource
                    if ($this->data instanceof StreamInterface || $this->data instanceof Stringable || is_resource($this->data)) {
                        $response->setBody($this->data);
                    }
                    // Promise
                    elseif ($this->data instanceof Promise) {
                        $response->setBody(json_encode(await($this->data)));
                    }
                    // Data object
                    elseif ($this->data instanceof DataObject) {
                        $response->setBody(strval($this->data));
                    }
                    // Arrayable
                    elseif ($this->data instanceof Arrayable) {
                        $response->setParameters($this->data->toArray());
                    }
                    // Iterable
                    elseif (is_iterable($this->data)) {
                        $response->setBody(json_encode(CollectionBaseDto::of($this->data)));
                    }
                    // Array or Object
                    elseif (is_array($this->data) || is_object($this->data)) {
                        $response->setBody(Stream::create(json_encode($this->data)));
                    }
                    // String or Number
                    elseif (is_string($this->data) || is_numeric($this->data)) {
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
