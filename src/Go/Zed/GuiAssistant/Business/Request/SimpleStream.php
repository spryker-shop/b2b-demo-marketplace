<?php

namespace Go\Zed\GuiAssistant\Business\Request;

use Psr\Http\Message\StreamInterface;

class SimpleStream implements StreamInterface {
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
