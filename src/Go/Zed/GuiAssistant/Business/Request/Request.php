<?php

namespace Go\Zed\GuiAssistant\Business\Request;



use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;


class Request implements \Psr\Http\Message\RequestInterface
{
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string|null
     */
    private $requestTarget;

    /**
     * @param string $httpMethod  GET | POST | PUT | PATCH | DELETE
     * @param string $resourcePath /product-abstracts/{abstractSku}
     * @param array $queryParams ['q' => 'search', 'limit' => 10]
     * @param array $pathParams ['abstractSku' => '123-abc']
     * @param array $payload ['name' => 'New Product', 'price' => 100]
     * @param string $endpoint /product-abstracts/123-abc
     */
    public function __construct(protected string $httpMethod, protected string $resourcePath, protected array $queryParams = [], protected array $pathParams = [], protected array $payload = [], protected string $endpoint)
    {
        // Set default headers
        $this->headers = [
            'Content-Type' => ['application/json'],
            'Accept' => ['application/json']
        ];
    }

    public function getProtocolVersion()
    {
        return '1.1';
    }

    public function withProtocolVersion($version)
    {
        $clone = clone $this;
        // Protocol version is immutable for this implementation
        return $clone;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    public function getHeader($name)
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine($name)
    {
        $value = $this->getHeader($name);
        if (is_array($value)) {
            return implode(",", $value);
        }
        return (string)$value;
    }

    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers[$name] = (array)$value;
        return $clone;
    }

    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        if (!isset($clone->headers[$name])) {
            $clone->headers[$name] = [];
        }
        $clone->headers[$name] = array_merge((array)$clone->headers[$name], (array)$value);
        return $clone;
    }

    public function withoutHeader($name)
    {
        $clone = clone $this;
        unset($clone->headers[$name]);
        return $clone;
    }

    public function getBody()
    {
        return new SimpleStream(json_encode($this->payload));
    }

    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        // Body is determined by payload, so this is immutable
        return $clone;
    }

    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        return $this->endpoint;
    }

    public function withRequestTarget($requestTarget)
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;
        return $clone;
    }

    public function getMethod()
    {
        return $this->httpMethod;
    }

    public function withMethod($method)
    {
        $clone = clone $this;
        $clone->httpMethod = $method;
        return $clone;
    }

    public function getUri()
    {
        // Build URI from endpoint and query parameters
        $uri = 'https://localhost' . $this->endpoint;

        if (!empty($this->queryParams)) {
            $queryString = http_build_query($this->queryParams);
            if ($queryString) {
                $uri .= '?' . $queryString;
            }
        }

        return new SimpleUri($uri);
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        // URI is determined by endpoint and query params, so this is immutable
        return $clone;
    }

    // Getter methods for accessing the constructor parameters
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getResourcePath(): string
    {
        return $this->resourcePath;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getPathParams(): array
    {
        return $this->pathParams;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}


class SimpleStream implements \Psr\Http\Message\StreamInterface {
    private $content;
    public function __construct($content) { $this->content = $content; }
    public function __toString() { return (string)$this->content; }
    public function close() {}
    public function detach() {}
    public function getSize() { return strlen($this->content); }
    public function tell() { return 0; }
    public function eof() { return true; }
    public function isSeekable() { return false; }
    public function seek($offset, $whence = SEEK_SET) {}
    public function rewind() {}
    public function isWritable() { return false; }
    public function write($string) { return 0; }
    public function isReadable() { return true; }
    public function read($length) { return substr($this->content, 0, $length); }
    public function getContents() { return $this->content; }
    public function getMetadata($key = null) { return null; }
}

class SimpleUri implements \Psr\Http\Message\UriInterface {
    private $uri;
    public function __construct($uri) { $this->uri = $uri; }
    public function __toString() { return $this->uri; }
    public function getScheme() { return parse_url($this->uri, PHP_URL_SCHEME) ?: ''; }
    public function getAuthority() { return ''; }
    public function getUserInfo() { return ''; }
    public function getHost() { return parse_url($this->uri, PHP_URL_HOST) ?: ''; }
    public function getPort() { return parse_url($this->uri, PHP_URL_PORT) ?: null; }
    public function getPath() { return parse_url($this->uri, PHP_URL_PATH) ?: ''; }
    public function getQuery() { return parse_url($this->uri, PHP_URL_QUERY) ?: ''; }
    public function getFragment() { return parse_url($this->uri, PHP_URL_FRAGMENT) ?: ''; }
    public function withScheme($scheme) { $clone = clone $this; return $clone; }
    public function withUserInfo($user, $password = null) { $clone = clone $this; return $clone; }
    public function withHost($host) { $clone = clone $this; return $clone; }
    public function withPort($port) { $clone = clone $this; return $clone; }
    public function withPath($path) { $clone = clone $this; return $clone; }
    public function withQuery($query) { $clone = clone $this; return $clone; }
    public function withFragment($fragment) { $clone = clone $this; return $clone; }
}
