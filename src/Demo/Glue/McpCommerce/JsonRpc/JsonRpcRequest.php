<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\JsonRpc;

/**
 * Immutable value object representing a single parsed JSON-RPC 2.0 request envelope.
 */
class JsonRpcRequest
{
    /**
     * @param string $method
     * @param array<string, mixed> $params
     * @param string|int|null $id
     * @param bool $isValid
     */
    public function __construct(
        protected readonly string $method = '',
        protected readonly array $params = [],
        protected readonly string|int|null $id = null,
        protected readonly bool $isValid = false,
    ) {
    }

    /**
     * Specification:
     * - Returns the JSON-RPC method name, or an empty string when the envelope is invalid.
     *
     * @api
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Specification:
     * - Returns the JSON-RPC params object as an associative array.
     *
     * @api
     *
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Specification:
     * - Returns the JSON-RPC request id, or null for a notification or an unparsable envelope.
     *
     * @api
     */
    public function getId(): string|int|null
    {
        return $this->id;
    }

    /**
     * Specification:
     * - Returns true when the envelope is a well-formed JSON-RPC 2.0 request.
     *
     * @api
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Specification:
     * - Returns true when the envelope carries no id and therefore expects no response body.
     *
     * @api
     */
    public function isNotification(): bool
    {
        return $this->id === null;
    }
}
