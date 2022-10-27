<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Enums\ResponseFormat;
use InvalidArgumentException;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

use Nyholm\Psr7\Stream;
use Stringable;

/**
 * HTTP Response Provider
 * 
 * PHP Mini Framework
 *
 * This class borrows heavily from the Symfony2 Framework and is part of the symfony package
 * @see Symfony\Component\HttpFoundation\Response (https://github.com/symfony/symfony)
 *
 * @see Busarm\PhpMini\Interface\ResponseInterface
 * 
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class Response implements ResponseInterface, Stringable
{
    /**
     * 
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $format = ResponseFormat::JSON;

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $statusText;

    /**
     * @var StreamInterface|Stringable|resource|string|null
     */
    protected $body = NULL;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var array
     */
    protected $httpHeaders = array();

    /**
     * @var boolean
     */
    private $clearBuffer = false;

    /**
     * @var array
     */
    public static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );

    /**
     * @param StreamInterface|\Stringable|resource|string|array|null $body
     * @param int   $statusCode
     * @param array $headers
     * @param string $version
     */
    public function __construct($body = null, $statusCode = 200, $headers = array(), $version = '1.1', $format = ResponseFormat::JSON)
    {
        $this->version = $version;
        $this->setFormat($format);
        $this->setStatusCode($statusCode);
        $this->setHttpHeaders($headers);
        if (is_array($body)) $this->setParameters($body);
        else $this->setBody($body);
    }

    /**
     * Create response object from PSR7 response
     * 
     * @return self
     */
    public static function fromPsr(MessageResponseInterface $psr): self
    {
        return new self($psr->getBody(), $psr->getStatusCode(), $psr->getHeaders(), $psr->getProtocolVersion());
    }

    /**
     * Converts the response object to string containing all headers and the response content.
     *
     * @return string The response with headers and content
     */
    public function __toString()
    {
        $headers = array();
        foreach ($this->httpHeaders as $name => $value) {
            $headers[$name] = (array) $value;
        }

        return
            sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . "\r\n" .
            $this->getHttpHeadersAsString($headers) . "\r\n" .
            $this->getResponseBody();
    }

    /**
     * Returns the build header line.
     *
     * @param string $name  The header name
     * @param string $value The header value
     *
     * @return string The built header line
     */
    protected function buildHeader($name, $value)
    {
        return sprintf("%s: %s\n", $name, $value);
    }

    /**
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     *
     * @param string $format
     *
     * @return self
     */
    public function setFormat($format = ResponseFormat::JSON): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @param string $text
     * @throws InvalidArgumentException
     * @return self
     */
    public function setStatusCode($statusCode, $text = null): self
    {
        $this->statusCode = (int) $statusCode;
        if ($this->isInvalid()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $statusCode));
        }

        $this->statusText = false === $text ? '' : (null === $text ? self::$statusTexts[$this->statusCode] : $text);
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * @param StreamInterface|Stringable|resource|string|null $body
     * @return self
     */
    public function setBody(mixed $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return StreamInterface|Stringable|resource|string|null
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function addParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }
    /**
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function setParameter($name, $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function getParameter($name, $default = null): mixed
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $httpHeaders
     * @return self
     */
    public function addHttpHeaders(array $httpHeaders): self
    {
        $headers = [];
        foreach ($httpHeaders as $key => $value) {
            if (is_array($value)) {
                if (count($value) == 2) {
                    $headers[strtolower($value[0])] = $value[1];
                }
            } else {
                $headers[strtolower($key)] = $value;
            }
        }
        $this->httpHeaders = array_merge($this->httpHeaders, $headers);
        return $this;
    }

    /**
     * @param array $httpHeaders
     * @return self
     */
    public function setHttpHeaders(array $httpHeaders): self
    {
        $headers = [];
        foreach ($httpHeaders as $key => $value) {
            if (is_array($value)) {
                if (count($value) == 2) {
                    $headers[strtolower($value[0])] = $value[1];
                }
            } else {
                $headers[strtolower($key)] = $value;
            }
        }
        $this->httpHeaders = $headers;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setHttpHeader($name, $value): self
    {
        $this->httpHeaders[strtolower($name)] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function getHttpHeader($name, $default = null): mixed
    {
        return isset($this->httpHeaders[strtolower($name)]) ? $this->httpHeaders[strtolower($name)] : $default;
    }

    /**
     * @return array
     */
    public function getHttpHeaders(): array
    {
        return $this->httpHeaders;
    }

    /**
     * Set Redirect Headers
     *
     * @param string $uri URL
     * @param int|false $refresh Refresh page timeout. False to disable refresh redirect
     * 
     * @throws InvalidArgumentException If invalid url
     * @return self
     */
    public function redirect($uri, $refresh = false): self
    {
        if (!preg_match('#^(\w+:)?//#i', $uri)) {
            throw new InvalidArgumentException("Invalid redirect uri: $uri");
        }

        if ($refresh) {
            $timeout = !is_bool($refresh) ? $refresh : 0;
            $this->setHttpHeader('Refresh', "$timeout;url=$uri");
        } else {
            $this->setHttpHeader('Location', $uri);
        }
        return $this;
    }

    /**
     * @return StreamInterface|Stringable|resource|string|null
     * @throws InvalidArgumentException
     */
    public function getResponseBody()
    {
        if (!empty($this->body)) {
            return is_string($this->body) ? $this->body : Stream::create($this->body);
        }

        switch ($this->format) {
            case ResponseFormat::JSON:
                return json_encode($this->parameters);
            case ResponseFormat::XML:
                // this only works for single-level arrays
                $xml = new \SimpleXMLElement('<response/>');
                foreach ($this->parameters as $key => $param) {
                    $xml->addChild($key, $param);
                }
                return $xml->asXML();
        }

        return null;
    }

    /**
     * @param bool $continue
     */
    public function send($continue = false)
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }

        // clear buffer
        if ($this->clearBuffer) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }

        try {
            // start buffer
            ob_start();
            switch ($this->format) {
                case ResponseFormat::JSON:
                    $this->setHttpHeader('Content-Type', 'application/json');
                    break;
                case ResponseFormat::XML:
                    $this->setHttpHeader('Content-Type', 'text/xml');
                    break;
                case ResponseFormat::HTML:
                    $this->setHttpHeader('Content-Type', 'text/html');
                    break;
                default:
                    if (!$this->getHttpHeader('Content-Type'))
                        $this->setHttpHeader('Content-Type', 'application/octet-stream');
            }

            // status
            header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));
            foreach ($this->getHttpHeaders() as $name => $header) {
                header(sprintf('%s: %s', $name, $header));
            }
            echo $this->getResponseBody();
            ob_end_flush();

            // Clear buffer on the next response
            $this->clearBuffer = !$continue;
            if (!$continue) die;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @param int $code response code
     * @return self
     */
    public function json($data, $code = 200): self
    {
        $this->setBody(null);
        $this->setParameters($data);
        $this->setStatusCode($code);
        $this->setFormat(ResponseFormat::JSON);
        return $this;
    }

    /**
     * @param mixed $data
     * @param int $code response code
     * @return self
     */
    public function xml($data, $code = 200): self
    {
        $this->setBody(null);
        $this->setParameters($data);
        $this->setStatusCode($code);
        $this->setFormat(ResponseFormat::XML);
        return $this;
    }

    /**
     * @param StreamInterface|string|null $data
     * @param int $code response code
     * @return self
     */
    public function html($data, $code = 200): self
    {
        $this->setParameters([]);
        $this->setBody($data);
        $this->setStatusCode($code);
        $this->setFormat(ResponseFormat::HTML);
        return $this;
    }

    /**
     * @param StreamInterface|string|null $data
     * @param string $name
     * @param bool $inline
     * @param string $contentType
     * @return self
     */
    public function download($data, $name = null, $inline = false, $contentType = null): self
    {
        $this->setParameters([]);
        $this->setBody($data);
        if ($name) $this->setHttpHeader('Content-Disposition', ($inline ? "inline; " : 'attachment; ') . "filename=\"$name\"");
        else $this->setHttpHeader('Content-Disposition', ($inline ? "inline; " : 'attachment; ') . "filename=\"download-" . time() . "\"");
        if ($contentType) $this->setHttpHeader('Content-Type', $contentType);
        $this->setFormat(ResponseFormat::BIN);
        return $this;
    }

    /**
     * @return Boolean
     *
     * @api
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param array $headers
     * @return string
     */
    private function getHttpHeadersAsString($headers)
    {
        if (count($headers) == 0) {
            return '';
        }

        $max = max(array_map('strlen', array_keys($headers))) + 1;
        $content = '';
        ksort($headers);
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $this->beautifyHeaderName($name) . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param string $name
     * @return mixed
     */
    private function beautifyHeaderName($name)
    {
        return preg_replace_callback('/\-(.)/', array($this, 'beautifyCallback'), ucfirst($name));
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param array $match
     * @return string
     */
    private function beautifyCallback($match)
    {
        return '-' . strtoupper($match[1]);
    }

    /**
     * Get PSR7 response
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function toPsr(): MessageResponseInterface
    {
        $response = new \Nyholm\Psr7\Response(
            $this->statusCode,
            $this->httpHeaders,
            $this->getResponseBody(),
            $this->version,
            $this->statusText
        );
        return $response;
    }
}
