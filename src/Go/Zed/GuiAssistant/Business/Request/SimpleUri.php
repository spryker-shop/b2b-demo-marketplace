<?php

namespace Go\Zed\GuiAssistant\Business\Request;

use Psr\Http\Message\UriInterface;

class SimpleUri implements UriInterface {
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
