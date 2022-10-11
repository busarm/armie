<?php

namespace Busarm\PhpMini\Handlers;

use Busarm\PhpMini\Enums\ResponseFormat;
use Busarm\PhpMini\Interfaces\Arrayable;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\ResponseHandlerInterface;
use Busarm\PhpMini\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
final class ResponseHandler implements ResponseHandlerInterface
{
    public function __construct(private mixed $data, private $version = '1.1', private $format = ResponseFormat::JSON)
    {
    }

    public function handle(): ResponseInterface
    {
        if ($this->data !== false) {
            if ($this->data instanceof ResponseInterface) {
                $response = $this->data;
            } else {
                $response = new Response(statusCode: 200, version: $this->version, format: $this->format);
                if ($this->data !== null) {
                    if ($this->data instanceof MessageResponseInterface) {
                        $response = Response::fromPsr($this->data);
                    } elseif ($this->data instanceof ResponseHandlerInterface) {
                        $response = $this->data->handle();
                    } elseif ($this->data instanceof Arrayable) {
                        $response->setParameters($this->data->toArray());
                    } elseif (is_array($this->data) || is_object($this->data)) {
                        $response->setBody(Stream::create(json_encode($this->data)));
                    } else {
                        $response->html(Stream::create($this->data), 200);
                    }
                }
            }
        } else {
            $response = new Response(statusCode: 404, version: $this->version, format: $this->format);
        }

        return $response;
    }
}
