<?php

namespace Busarm\PhpMini\Interfaces;

use Busarm\PhpMini\Enums\ResponseFormat;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Stringable;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @codeCoverageIgnore
 */
interface ResponseInterface extends Stringable
{
    /**
     * @param string $format
     * @return self
     */
    public function setFormat($format = ResponseFormat::JSON): self;

    /**
     * @return string
     */
    public function getFormat(): string;

    /**
     * @param int $statusCode
     * @param string|null $text
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
     * @return self
     */
    public function setBody(mixed $body): self;

    /**
     * @return \Psr\Http\Message\StreamInterface|Stringable|resource|string|null
     */
    public function getBody(): mixed;

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self;

    /**
     * @param array $parameters
     * @return self
     */
    public function addParameters(array $parameters): self;

    /**
     * @param string $name
     * @param mixed  $default
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
     * @return self
     */
    public function setParameter($name, $value): self;

    /**
     * @param array $httpHeaders
     * @return self
     */
    public function addHttpHeaders(array $httpHeaders): self;

    /**
     * @param array $httpHeaders
     * @return self
     */
    public function setHttpHeaders(array $httpHeaders): self;

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setHttpHeader($name, $value): self;

    /**
     * @param string $name
     * @return mixed
     */
    public function getHttpHeader($name): mixed;

    /**
     * @return array
     */
    public function getHttpHeaders(): array;

    /**
     * Set Redirect Headers
     *
     * @param string $uri URL
     * @param int|false $refresh Refresh page timeout. False to disable refresh redirect
     * 
     * @return self
     */
    public function redirect($uri, $refresh = false): self;

    /**
     * @param bool $continue
     */
    public function send($continue = false);

    /**
     * @param array $data
     * @param int $code response code
     * @return self
     */
    public function json($data, $code = 200): self;

    /**
     * @param array $data
     * @param int $code response code
     * @return self
     */
    public function xml($data, $code = 200): self;

    /**
     * @param \Psr\Http\Message\StreamInterface|string|null $data
     * @param int $code response code
     * @return self
     */
    public function html($data, $code = 200): self;

    /**
     * @param \Psr\Http\Message\StreamInterface|string|null $data
     * @param string $name
     * @param bool $inline
     * @param string $contentType
     * @return self
     */
    public function download($data, $name = null, $inline = false, $contentType = null): self;

    /**
     * @return Boolean
     *
     * @api
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isInformational();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isSuccessful();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isRedirection();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isClientError();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isServerError();

    /**
     * @return MessageResponseInterface
     */
    public function toPsr(): MessageResponseInterface;
}
