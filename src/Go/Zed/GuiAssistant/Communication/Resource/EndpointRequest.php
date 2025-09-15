<?php

namespace Go\Zed\GuiAssistant\Communication\Resource;


use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

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

class EndpointRequest implements \Psr\Http\Message\RequestInterface
{
    /**
     * @var array
     */
    private $headers;

    /**
     * @var string|null
     */
    private $requestTarget;

    public function __construct(protected SymfonyRequest $symfonyRequest, protected string $endpoint)
    {
        $this->headers = $symfonyRequest->headers->all();
    }

    public function getProtocolVersion()
    {
        return $this->symfonyRequest->server->get('SERVER_PROTOCOL', '1.1');
    }

    public function withProtocolVersion($version)
    {
        $clone = clone $this;
        $clone->symfonyRequest->server->set('SERVER_PROTOCOL', $version);
        return $clone;
    }

    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $name => $values) {
            $headers[$name] = (array)$values;
        }

        return $headers;
    }

    public function hasHeader($name)
    {
        return $this->symfonyRequest->headers->has($name);
    }

    public function getHeader($name)
    {
        $value = $this->symfonyRequest->headers->get($name, null);
        if ($value === null) {
            return [];
        }
        if (is_string($value)) {
            return array_map('trim', explode(',', $value));
        }
        return (array)$value;
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
        return new SimpleStream($this->symfonyRequest->getContent());
    }

    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        // Not supported: Symfony Request body is not mutable
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
        return $this->symfonyRequest->getMethod();
    }

    public function withMethod($method)
    {
        $clone = clone $this;
        $clone->symfonyRequest->setMethod($method);
        return $clone;
    }

    public function getUri()
    {
        $schemeHost = $this->symfonyRequest->getSchemeAndHttpHost();

        // Use the endpoint directly from constructor
        $uri = $schemeHost . $this->endpoint;

        $queryString = $this->symfonyRequest->getQueryString();
        if ($queryString) {
            $filteredQuery = ltrim($queryString, '&');

            if ($filteredQuery) {
                $uri .= '?' . $filteredQuery;
            }
        }

        return new SimpleUri($uri);
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        // Not supported: Symfony Request URI is not mutable
        return $clone;
    }
}
