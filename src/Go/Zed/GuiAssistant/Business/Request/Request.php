<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Business\Request;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
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
     * @param string $httpMethod GET | POST | PUT | PATCH | DELETE
     * @param string $resourcePath /product-abstracts/{abstractSku}
     * @param array $queryParams ['q' => 'search', 'limit' => 10]
     * @param array $pathParams ['abstractSku' => '123-abc']
     * @param array $payload ['name' => 'New Product', 'price' => 100]
     * @param string $endpoint /product-abstracts/123-abc
     */
    public function __construct(
        protected string $httpMethod,
        protected string $resourcePath,
        protected array $queryParams,
        protected array $pathParams,
        protected array $payload,
        protected string $endpoint,
    ) {
        // Set default headers
        $this->headers = [
            'Content-Type' => ['application/json'],
            'Accept' => ['application/json'],
        ];
    }

    public function getProtocolVersion()
    {
        return '1.1';
    }

    public function withProtocolVersion($version)
    {
        return clone $this;
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
            return implode(',', $value);
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
        return clone $this;
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

        if ($this->queryParams) {
            $queryString = http_build_query($this->queryParams);
            if ($queryString) {
                $uri .= '?' . $queryString;
            }
        }

        return new SimpleUri($uri);
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return clone $this;
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
