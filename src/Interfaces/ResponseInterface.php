<?php

namespace Armie\Interfaces;

use Armie\Enums\ResponseFormat;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Stringable;
use Workerman\Protocols\Http\Response as HttpResponse;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @codeCoverageIgnore
 */
interface ResponseInterface extends Stringable
{
    /**
     * @param ResponseFormat $format
     *
     * @return self
     */
    public function setFormat(ResponseFormat $format = ResponseFormat::JSON): self;

    /**
     * @return ResponseFormat
     */
    public function getFormat(): ResponseFormat;

    /**
     * @param int         $statusCode
     * @param string|null $text
     *
     * @return self
     */
    public function setStatusCode($statusCode, $text = null): self;

    /**
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * @return string
     */
    public function getStatusText(): string;

    /**
     * @param \Psr\Http\Message\StreamInterface|Stringable|resource|string|null $body
     *
     * @return self
     */
    public function setBody(mixed $body): self;

    /**
     * @return \Psr\Http\Message\StreamInterface|Stringable|resource|string|null
     */
    public function getBody(): mixed;

    /**
     * @param array $parameters
     *
     * @return self
     */
    public function setParameters(array $parameters): self;

    /**
     * @param array $parameters
     *
     * @return self
     */
    public function addParameters(array $parameters): self;

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null): mixed;

    /**
     * @return array
     */
    public function getParameters(): array;

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function setParameter($name, $value): self;

    /**
     * @param array $httpHeaders
     *
     * @return self
     */
    public function addHttpHeaders(array $httpHeaders): self;

    /**
     * @param array $httpHeaders
     *
     * @return self
     */
    public function setHttpHeaders(array $httpHeaders): self;

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function setHttpHeader($name, $value): self;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getHttpHeader($name): mixed;

    /**
     * @return array
     */
    public function getHttpHeaders(): array;

    /**
     * Set Redirect Headers.
     *
     * @param string    $uri     URL
     * @param int|false $refresh Refresh page timeout. False to disable refresh redirect
     *
     * @return self
     */
    public function redirect($uri, $refresh = false): self;

    /**
     * Perform actions before sending response.
     *
     * @return self
     */
    public function prepare(): self;

    /**
     * @param bool $continue
     *
     * @return self
     */
    public function send($continue = false): self;

    /**
     * @param \Psr\Http\Message\StreamInterface|string|null $data
     * @param int                                           $code response code
     *
     * @return self
     */
    public function raw($data, $code = 200): self;

    /**
     * @param array $data
     * @param int   $code response code
     *
     * @return self
     */
    public function json($data, $code = 200): self;

    /**
     * @param array $data
     * @param int   $code response code
     *
     * @return self
     */
    public function xml($data, $code = 200): self;

    /**
     * @param \Psr\Http\Message\StreamInterface|string|null $data
     * @param int                                           $code response code
     *
     * @return self
     */
    public function html($data, $code = 200): self;

    /**
     * @param \Psr\Http\Message\StreamInterface|string|null $data
     * @param string                                        $name
     * @param bool                                          $inline
     * @param string                                        $contentType
     *
     * @return self
     */
    public function download($data, $name = null, $inline = false, $contentType = null): self;

    /**
     * @param string $path
     * @param string $name
     * @param bool   $inline
     * @param string $contentType
     *
     * @return self
     */
    public function downloadFile(string $path, $name = null, $inline = false, $contentType = null): self;

    /**
     * @return bool
     *
     * @api
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid();

    /**
     * @return bool
     *
     * @api
     */
    public function isInformational();

    /**
     * @return bool
     *
     * @api
     */
    public function isSuccessful();

    /**
     * @return bool
     *
     * @api
     */
    public function isRedirection();

    /**
     * @return bool
     *
     * @api
     */
    public function isClientError();

    /**
     * @return bool
     *
     * @api
     */
    public function isServerError();

    /**
     * @return MessageResponseInterface
     */
    public function toPsr(): MessageResponseInterface;

    /**
     * @return HttpResponse
     */
    public function toWorkerman(): HttpResponse;
}
