<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

interface ToolRegistryInterface
{
    /**
     * Specification:
     * - Returns the MCP tool descriptors advertised by `tools/list`.
     * - Each descriptor carries a `name`, a `description` and an `inputSchema`.
     *
     * @api
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolDescriptors(): array;

    /**
     * Specification:
     * - Returns the tool registered under the given name, or null when no tool matches.
     *
     * @api
     */
    public function findToolByName(string $name): ?ToolInterface;
}
