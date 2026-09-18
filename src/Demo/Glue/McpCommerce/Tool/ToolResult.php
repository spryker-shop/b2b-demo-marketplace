<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

/**
 * Immutable outcome of an MCP tool call.
 *
 * A failed tool call is a successful JSON-RPC response carrying `isError: true`, which is how the MCP
 * specification distinguishes a tool-level failure from a protocol-level one. Failures therefore
 * travel as data through this object rather than as exceptions, so no stack trace can ever reach a
 * client.
 */
class ToolResult
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        private readonly bool $isSuccessful,
        private readonly array $data = [],
        private readonly string $message = '',
    ) {
    }

    /**
     * Specification:
     * - Creates a successful tool result carrying the structured payload for the client.
     *
     * @api
     *
     * @param array<string, mixed> $data
     */
    public static function createSuccess(array $data): self
    {
        return new self(true, $data);
    }

    /**
     * Specification:
     * - Creates a failed tool result carrying a client-facing message and no payload.
     *
     * @api
     */
    public static function createError(string $message): self
    {
        return new self(false, [], $message);
    }

    /**
     * Specification:
     * - Returns true when the tool call completed successfully.
     *
     * @api
     */
    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    /**
     * Specification:
     * - Returns the structured tool payload, empty for a failed call.
     *
     * @api
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Specification:
     * - Returns the client-facing failure message, empty for a successful call.
     *
     * @api
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
