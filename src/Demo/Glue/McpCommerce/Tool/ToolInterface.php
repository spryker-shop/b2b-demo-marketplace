<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

use Generated\Shared\Transfer\McpIdentityTransfer;

interface ToolInterface
{
    /**
     * Specification:
     * - Returns the unique MCP tool name used by `tools/call` to address this tool.
     *
     * @api
     */
    public function getName(): string;

    /**
     * Specification:
     * - Returns the human readable description advertised by `tools/list`.
     *
     * @api
     */
    public function getDescription(): string;

    /**
     * Specification:
     * - Returns the JSON Schema describing the tool's `arguments` object.
     *
     * @api
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array;

    /**
     * Specification:
     * - Executes the tool for the given customer identity and call arguments.
     * - Reaches the shop exclusively through an internal Storefront API sub-request, so no cart,
     *   checkout or catalog business logic is reimplemented here.
     * - Never throws: every failure is returned as an unsuccessful result carrying a message.
     *
     * @api
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(McpIdentityTransfer $mcpIdentityTransfer, array $arguments): ToolResult;
}
